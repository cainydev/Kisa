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
    public function handle(): void
    {
        $token = config('backup.token');
        $url = config('app.prod_url') . route('backup', absolute: false);

        $this->info("Fetching...");
        $response = Http::withHeader('Authorization', "Bearer $token")->get($url);

        if ($response->successful()) {
            $this->info("Request successful!");
        } else {
            $this->error("Failed to make request: " . $response->reason());
            return;
        }

        $temp = TemporaryDirectory::make();
        $path = $temp->path('backup.zip');
        Storage::put($path, $response->body());

        $zip = new ZipArchive;

        if (!$zip->open($path)) {
            $this->error("Failed to open zip.");
            return;
        }

        if (!$zip->extractTo($temp->path())) {
            $this->error("Failed to extract zip.");
        }

        Storage::delete($path);

        $this->info("Loading database...");
        $this->call(LoadDatabase::class, ['file' => $temp->path('database.sql')]);
    }
}
