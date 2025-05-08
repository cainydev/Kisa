<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DumpDatabase extends Command
{
    protected $signature = 'db:dump {path : The file path to dump the database to}';
    protected $description = 'Dump the entire database to a given file path';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $path = $this->argument('path');

        $connection = config('database.default');
        $driver = config("database.connections.$connection.driver");

        $username = config("database.connections.$connection.username");
        $password = config("database.connections.$connection.password");
        $host = config("database.connections.$connection.host", "127.0.0.1");
        $database = config("database.connections.$connection.database");

        $this->info("Using connection: $connection ($driver)");

        switch ($driver) {
            case 'mysql':
                $command = [
                    'mysqldump',
                    '-u' . $username,
                    '-p' . $password,
                    '-h' . $host,
                    $database,
                    '-r', $path,
                ];
                break;

            case 'pgsql':
                putenv("PGPASSWORD={$password}");
                $command = [
                    'pg_dump',
                    '-U', $username,
                    '-h', $host,
                    '-d', $database,
                    '-f', $path,
                ];
                break;

            case 'sqlite':
                $dbPath = $database;
                if (!file_exists($dbPath)) {
                    $this->error("SQLite database file not found: $dbPath");
                    return static::FAILURE;
                }
                $command = [
                    'sqlite3',
                    $dbPath,
                    '.dump',
                ];
                break;

            case 'sqlsrv':
                $this->error('SQL Server (sqlsrv) is not currently supported by this command.');
                return static::FAILURE;

            default:
                $this->error("Unsupported database driver: $driver");
                return static::FAILURE;
        }

        $this->info("Dumping database to: $path");

        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error("Dump failed: " . $process->getErrorOutput());
            return static::FAILURE;
        }

        if ($driver === 'sqlite') {
            file_put_contents($path, $process->getOutput());
        }

        $this->info("Database dumped successfully.");
        return static::SUCCESS;
    }
}
