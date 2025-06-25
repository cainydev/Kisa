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
        $port = config("database.connections.$connection.port");
        $database = config("database.connections.$connection.database");

        $this->info("Using connection: $connection ($driver)");
        $this->info("Loading dump from: $file");

        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                return $this->loadMysqlDump($file, $username, $password, $host, $port, $database, $driver);

            case 'pgsql':
                return $this->loadPostgresDump($file, $username, $password, $host, $port, $database);

            case 'sqlite':
                return $this->loadSqliteDump($file, $database);

            default:
                $this->error("Unsupported database driver: $driver");
                return static::FAILURE;
        }
    }

    private function loadMysqlDump(string $file, string $username, string $password, string $host, ?int $port, string $database, string $driver): int
    {
        $command = $driver === 'mariadb' ? 'mariadb' : 'mysql';

        // Build command parts
        $cmd = [
            $command,
            "--user=$username",
            "--host=$host",
            "--database=$database"
        ];

        if ($port) {
            $cmd[] = "--port=$port";
        }

        // Handle password
        if (!empty($password)) {
            $cmd[] = "--password=$password";
        }

        // Add input file redirection
        $cmd[] = "<";
        $cmd[] = escapeshellarg($file);

        // Convert to shell command
        $shellCommand = implode(' ', $cmd);

        $this->info("Executing: " . str_replace("--password=$password", "--password=***", $shellCommand));

        $process = Process::fromShellCommandline($shellCommand);
        $process->setTimeout(300); // 5 minutes timeout

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->line($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            $this->error("Load failed with exit code: " . $process->getExitCode());
            $this->error("Error output: " . $process->getErrorOutput());
            return static::FAILURE;
        }

        $this->info("Database loaded successfully.");
        return static::SUCCESS;
    }

    private function loadPostgresDump(string $file, string $username, string $password, string $host, ?int $port, string $database): int
    {
        // Set password via environment variable
        $env = ['PGPASSWORD' => $password];

        $cmd = [
            'psql',
            "--username=$username",
            "--host=$host",
            "--dbname=$database",
            "--file=$file"
        ];

        if ($port) {
            $cmd[] = "--port=$port";
        }

        $process = new Process($cmd, null, $env);
        $process->setTimeout(300);

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->line($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            $this->error("Load failed: " . $process->getErrorOutput());
            return static::FAILURE;
        }

        $this->info("Database loaded successfully.");
        return static::SUCCESS;
    }

    private function loadSqliteDump(string $file, string $database): int
    {
        if (!file_exists($database)) {
            $this->error("SQLite database file not found: $database");
            return static::FAILURE;
        }

        $sql = file_get_contents($file);
        $process = new Process(['sqlite3', $database]);
        $process->setInput($sql);
        $process->setTimeout(300);

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->line($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            $this->error("Load failed: " . $process->getErrorOutput());
            return static::FAILURE;
        }

        $this->info("Database loaded successfully.");
        return static::SUCCESS;
    }
}
