<?php

namespace App\Console\Commands;

use App\Support\Media\UploadTarget;
use Illuminate\Console\Command;

class AttachDocument extends Command
{
    protected $signature = 'media:attach
        {path : Path to the PDF to attach.}
        {type : What it belongs to — delivery or certificate.}
        {id : The delivery or certificate id.}
        {collection : Which document — invoice, deliveryNote, certificate or document.}';

    protected $description = 'Attach a local PDF to a delivery or certificate, for bulk-importing an existing document archive.';

    public function handle(): int
    {
        $path = $this->argument('path');
        $type = $this->argument('type');
        $collection = $this->argument('collection');

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}.");

            return self::FAILURE;
        }

        if (! UploadTarget::supports($type, $collection)) {
            $this->error("Unknown target \"{$type}/{$collection}\". Accepted: ".implode(', ', UploadTarget::pairs()).'.');

            return self::FAILURE;
        }

        if (mime_content_type($path) !== 'application/pdf') {
            $this->error("{$path} is not a PDF.");

            return self::FAILURE;
        }

        $record = UploadTarget::resolve($type, (int) $this->argument('id'));

        if ($record === null) {
            $this->error("No {$type} with id {$this->argument('id')}.");

            return self::FAILURE;
        }

        $media = $record->copyMedia($path)->toMediaCollection($collection);

        $this->info("Attached {$media->file_name} as {$collection} to ".UploadTarget::describe($record).'.');

        return self::SUCCESS;
    }
}
