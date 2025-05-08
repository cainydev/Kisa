<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Symfony\Component\Process\Process;

class LoadDatabase extends Command
{
    protected $signature = 'db:load {file : The SQL dump file to load into the database}';
    protected $description = 'Load a SQL dump into the current database connection';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return static::FAILURE;
        }

        if (App::environment('production')) {
            if (!$this->confirm('You are in production! Do you really want to load this database dump? This will overwrite data.', false)) {
                $this->warn("Aborted.");
                return static::SUCCESS;
            }
        }

        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        $username = config("database.connections.$connection.username");
        $password = config("database.connections.$connection.password");
        $host = config("database.connections.$connection.host", "127.0.0.1");
        $database = config("database.connections.$connection.database");

        $this->info("Using connection: $connection ($driver)");
        $this->info("Loading dump from: $file");

        switch ($driver) {
            case 'mysql':
                $command = [
                    'mysql',
                    '-u' . $username,
                    '-p' . $password,
                    '-h' . $host,
                    $database,
                ];
                break;

            case 'pgsql':
                putenv("PGPASSWORD={$password}");
                $command = [
                    'psql',
                    '-U', $username,
                    '-h', $host,
                    '-d', $database,
                    '-f', $file,
                ];
                break;

            case 'sqlite':
                $dbPath = $database;
                if (!file_exists($dbPath)) {
                    $this->error("SQLite database file not found: $dbPath");
                    return static::FAILURE;
                }

                $sql = file_get_contents($file);
                $command = [
                    'sqlite3',
                    $dbPath,
                ];
                break;

            case 'sqlsrv':
                $this->error('SQL Server (sqlsrv) is not currently supported by this command.');
                return static::FAILURE;

            default:
                $this->error("Unsupported database driver: $driver");
                return static::FAILURE;
        }

        if ($driver === 'sqlite') {
            $process = Process::fromShellCommandline("echo " . escapeshellarg($sql) . " | sqlite3 $dbPath");
        } elseif ($driver === 'mysql') {
            $process = Process::fromShellCommandline("mysql -u$username -p$password -h$host $database < $file");
        } else {
            $process = new Process($command);
        }

        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Load failed: " . $process->getErrorOutput());
            return static::FAILURE;
        }

        $this->info("Database loaded successfully.");
        return static::SUCCESS;
    }
}
