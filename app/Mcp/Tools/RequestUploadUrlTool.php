<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesEntities;
use App\Models\Certificate;
use App\Support\Media\UploadTarget;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Request a short-lived signed URL for uploading a PDF (invoice, delivery note or organic certificate) to a delivery or a certificate. The file is then PUT/POSTed to that URL as multipart field "file" — documents are never sent through this server.')]
class RequestUploadUrlTool extends Tool
{
    use ResolvesEntities;

    /**
     * How long a minted upload URL stays valid.
     */
    private const TTL_MINUTES = 30;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'target_type' => ['required', 'string', Rule::in(UploadTarget::types())],
            'target' => 'required',
            'collection' => 'required|string',
        ], [
            'target_type.in' => 'Target type must be one of: '.implode(', ', UploadTarget::types()).'.',
            'target.required' => 'Provide the delivery id, or the certificate id/number.',
            'collection.required' => 'Provide the collection, one of: '.implode(', ', UploadTarget::pairs()).'.',
        ]);

        $type = $validated['target_type'];
        $collection = $validated['collection'];

        if (! UploadTarget::supports($type, $collection)) {
            return Response::error(
                "Collection \"{$collection}\" is not available on {$type}. Accepted: "
                .implode(', ', UploadTarget::pairs()).'.'
            );
        }

        $record = $this->resolveUploadTarget($type, $validated['target']);

        if ($record === null) {
            return Response::error("No {$type} matching \"{$validated['target']}\".");
        }

        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        $url = URL::temporarySignedRoute('media.upload', $expiresAt, [
            'type' => $type,
            'id' => $record->getKey(),
            'collection' => $collection,
        ]);

        return Response::text(
            "Upload URL for {$collection} on ".UploadTarget::describe($record).":\n"
            ."{$url}\n\n"
            .'POST the PDF there as multipart form field "file", e.g.:'."\n"
            ."  curl -F 'file=@dokument.pdf' '{$url}'\n\n"
            .'Valid until '.$expiresAt->format('d.m.Y H:i').'. The collection holds a single '
            .'file, so uploading replaces any document already attached.'
        );
    }

    /**
     * Resolve an upload target, accepting the identifiers a chatbot user would
     * type: a numeric id for either type, or a certificate number.
     */
    private function resolveUploadTarget(string $type, int|string $identifier): mixed
    {
        if ($type === 'certificate' && ! is_numeric($identifier)) {
            $needle = mb_strtolower(trim((string) $identifier));

            return Certificate::query()->get()->first(
                fn (Certificate $certificate): bool => mb_strtolower((string) $certificate->certificate_number) === $needle
            );
        }

        if (! is_numeric($identifier)) {
            return null;
        }

        return UploadTarget::resolve($type, (int) $identifier);
    }

    /**
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'target_type' => $schema->string()
                ->description('What the document belongs to: "delivery" or "certificate".')
                ->required(),
            'target' => $schema->string()
                ->description('The delivery id, or a certificate id / certificate number.')
                ->required(),
            'collection' => $schema->string()
                ->description('Which document: '.implode(', ', UploadTarget::pairs()).'.')
                ->required(),
        ];
    }
}
