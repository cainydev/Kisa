<?php

namespace App\Services\DocumentExtraction;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Cache-backed status handle for an asynchronous AI extraction run.
 *
 * The UI holds the {@see $id} returned by {@see start()}, polls {@see find()}
 * until the state is `completed` or `failed`, then reads {@see $result}. This
 * is the standard mechanism for async AI processing across the app: dispatch a
 * job, hand the id to the UI, poll, apply the result on completion.
 */
class ExtractionStatus
{
    public const STATE_PENDING = 'pending';

    public const STATE_PROCESSING = 'processing';

    public const STATE_COMPLETED = 'completed';

    public const STATE_FAILED = 'failed';

    private const TTL_SECONDS = 3600;

    /**
     * @param  array<string, mixed>|null  $result
     */
    public function __construct(
        public string $id,
        public string $state,
        public ?array $result = null,
        public ?string $error = null,
    ) {}

    /**
     * Create a new pending status and persist it, returning the handle.
     */
    public static function start(): self
    {
        $status = new self((string) Str::uuid7(), self::STATE_PENDING);
        $status->persist();

        return $status;
    }

    /**
     * Load a status by id, or null if it has expired or never existed.
     */
    public static function find(string $id): ?self
    {
        $data = Cache::get(self::cacheKey($id));

        if (! is_array($data)) {
            return null;
        }

        return new self(
            id: $id,
            state: $data['state'],
            result: $data['result'] ?? null,
            error: $data['error'] ?? null,
        );
    }

    public function markProcessing(): void
    {
        $this->state = self::STATE_PROCESSING;
        $this->persist();
    }

    /**
     * @param  array<string, mixed>  $result
     */
    public function markCompleted(array $result): void
    {
        $this->state = self::STATE_COMPLETED;
        $this->result = $result;
        $this->persist();
    }

    public function markFailed(string $error): void
    {
        $this->state = self::STATE_FAILED;
        $this->error = $error;
        $this->persist();
    }

    public function isFinished(): bool
    {
        return in_array($this->state, [self::STATE_COMPLETED, self::STATE_FAILED], true);
    }

    private function persist(): void
    {
        Cache::put(self::cacheKey($this->id), [
            'state' => $this->state,
            'result' => $this->result,
            'error' => $this->error,
        ], self::TTL_SECONDS);
    }

    private static function cacheKey(string $id): string
    {
        return "ai-processing:{$id}";
    }
}
