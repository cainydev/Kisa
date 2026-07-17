<?php

namespace App\Services\DocumentExtraction;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Files\Document;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Generic driver for structured document extraction. Given an extraction
 * agent (which supplies the prompt + schema) and a PDF, it runs the configured
 * provider/model and returns the validated structured array.
 *
 * Concrete agents: {@see CertificateExtractionAgent}, {@see DeliveryNoteExtractionAgent},
 * {@see InvoiceExtractionAgent}.
 */
class DocumentExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function fromMedia(Agent $agent, Media $media): array
    {
        return $this->fromPath($agent, $media->getPath());
    }

    /**
     * @return array<string, mixed>
     */
    public function fromPath(Agent $agent, string $path): array
    {
        $response = $agent->prompt(
            'Extract the requested fields from the attached document.',
            attachments: [Document::fromPath($path)],
            provider: config('business.document_extraction.provider'),
            model: config('business.document_extraction.model'),
            timeout: (int) config('business.document_extraction.timeout'),
        );

        return $response->toArray();
    }
}
