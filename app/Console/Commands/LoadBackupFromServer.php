<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use ZipArchive;

class LoadBackupFromServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:latest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tries to fetch the latest backup from the server.';

    /**
     * Execute the console command.
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $token = config('backup.token');
        $url = config('app.prod_url') . route('backup', absolute: false);

        $this->info("Fetching...");
        $response = Http::withHeader('Authorization', "Bearer $token")->get($url);

        if ($response->successful()) {
            $this->info("Request successful!");
        } else {
            $this->error("Failed to make request: " . $response->reason());
            return static::FAILURE;
        }

        $tempDir = TemporaryDirectory::make()->deleteWhenDestroyed();
        $temp = Storage::build([
            'driver' => 'local',
            'root' => $tempDir->path(),
        ]);

        $file = 'backup.zip';
        $temp->put($file, $response->body());

        $zip = new ZipArchive;

        // db-dumps/xxx.sql
        // other_dirs/files
        if (!$zip->open($temp->path($file))) {
            $this->error("Failed to open zip.");
            return static::FAILURE;
        }

        if (!$zip->extractTo($temp->path(''))) {
            $this->error("Failed to extract zip.");
        }

        $zip->close();
        $temp->delete($file);

        $dump = null;
        foreach ($temp->files('db-dumps') as $file) {
            if (str($file)->endsWith('.sql')) {
                $dump = $file;
                break;
            }
        }

        if ($dump === null) {
            $this->error("Couldn't find sql dump.");
            return static::FAILURE;
        }

        $this->info("Loading database...");
        if ($this->call(LoadDatabase::class, ['file' => $temp->path($dump)]) === static::FAILURE) {
            $this->error("Failed to load database.");
            return static::FAILURE;
        }

        $temp->deleteDirectory('db-dumps');

        $this->info("Syncing files from backup...");

        $project = Storage::build([
            'driver' => 'local',
            'root' => config('backup.backup.source.files.relative_path', base_path()),
        ]);

        $directories =
            collect($temp->allDirectories())
                ->sortBy(fn($d1, $d2) => str($d1)->length() - str($d2)->length());

        foreach ($directories as $dir) {
            $project->makeDirectory($dir);
        }

        $files = collect($temp->allFiles());
        foreach ($files as $file) {
            $project->put($file, $temp->get($file));
        }

        $this->info("Files synced: " . $files->count());
        $this->info("Directories created: " . $directories->count());

        $this->info("Backup restore completed successfully!");
        return static::SUCCESS;
    }
}
