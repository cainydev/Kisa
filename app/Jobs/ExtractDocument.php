<?php

namespace App\Jobs;

use App\Services\DocumentExtraction\DocumentExtractor;
use App\Services\DocumentExtraction\ExtractionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Laravel\Ai\Contracts\Agent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

/**
 * Runs a structured document extraction asynchronously and records the result
 * in the cache-backed {@see ExtractionStatus} the UI polls.
 */
class ExtractDocument implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * AI providers return transient overload errors, so retry before failing.
     */
    public int $tries = 3;

    /**
     * @param  class-string<Agent>  $agentClass  The extraction agent to run.
     */
    public function __construct(
        public string $agentClass,
        public Media $media,
        public string $statusId,
    ) {}

    /**
     * Backoff (seconds) between retries to ride out provider overloads.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [5, 15];
    }

    public function handle(DocumentExtractor $extractor): void
    {
        $status = ExtractionStatus::find($this->statusId);

        if ($status === null) {
            return;
        }

        $status->markProcessing();

        $agent = app($this->agentClass);

        $status->markCompleted($extractor->fromMedia($agent, $this->media));
    }

    public function failed(Throwable $exception): void
    {
        ExtractionStatus::find($this->statusId)?->markFailed($exception->getMessage());
    }
}
