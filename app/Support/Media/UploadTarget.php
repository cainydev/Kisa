<?php

namespace App\Support\Media;

use App\Models\Certificate;
use App\Models\Delivery;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

/**
 * The models and collections that may receive an uploaded document, and the
 * rules for resolving them. Uploads are signed URLs handed to untrusted
 * clients, so the target must be validated against this allowlist rather than
 * taken from the request: it is what stops a signature minted for one delivery
 * from writing into an unrelated model or an unregistered collection.
 */
final class UploadTarget
{
    /**
     * Model class and permitted media collections, keyed by the public type
     * name used in the MCP tool and the signed route.
     *
     * @var array<string, array{model: class-string<Model&HasMedia>, collections: list<string>}>
     */
    private const TARGETS = [
        'delivery' => [
            'model' => Delivery::class,
            'collections' => ['invoice', 'deliveryNote', 'certificate'],
        ],
        'certificate' => [
            'model' => Certificate::class,
            'collections' => ['document'],
        ],
    ];

    /**
     * Every accepted "type/collection" pair, for error messages and schema docs.
     *
     * @return list<string>
     */
    public static function pairs(): array
    {
        $pairs = [];

        foreach (self::TARGETS as $type => $target) {
            foreach ($target['collections'] as $collection) {
                $pairs[] = "{$type}/{$collection}";
            }
        }

        return $pairs;
    }

    /**
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::TARGETS);
    }

    public static function supports(string $type, string $collection): bool
    {
        return isset(self::TARGETS[$type])
            && in_array($collection, self::TARGETS[$type]['collections'], true);
    }

    /**
     * Resolve the model that an upload targets, or null when the type is
     * unknown or no record has that id.
     */
    public static function resolve(string $type, int $id): (Model&HasMedia)|null
    {
        if (! isset(self::TARGETS[$type])) {
            return null;
        }

        /** @var class-string<Model&HasMedia> $model */
        $model = self::TARGETS[$type]['model'];

        return $model::find($id);
    }

    /**
     * A human-readable label for the resolved record, used in tool responses.
     */
    public static function describe(Model $record): string
    {
        return match (true) {
            $record instanceof Delivery => 'Lieferung #'.$record->id
                .' ('.($record->supplier?->shortname ?? '?').', '
                .$record->delivered_date->format('d.m.Y').')',
            $record instanceof Certificate => 'Zertifikat #'.$record->id
                .' ('.$record->certificate_number.')',
            default => class_basename($record).' #'.$record->getKey(),
        };
    }
}
