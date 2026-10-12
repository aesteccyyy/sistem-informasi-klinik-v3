<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=14 : Number of recent backups to retain}';

    protected $description = 'Create a compressed private backup of the MySQL database';

    public function handle(): int
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            $this->error('This backup command currently supports MySQL only.');

            return self::FAILURE;
        }

        $keep = filter_var($this->option('keep'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($keep === false) {
            $this->error('--keep must be a positive integer.');

            return self::FAILURE;
        }

        $directory = storage_path('app/private/database-backups');
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            $this->error('Could not create the private backup directory.');

            return self::FAILURE;
        }
        @chmod($directory, 0700);

        $file = $directory . '/simklinik-' . now()->format('Y-m-d_His') . '.sql.gz';
        $output = gzopen($file, 'wb9');
        if ($output === false) {
            $this->error('Could not open the backup file for writing.');

            return self::FAILURE;
        }

        try {
            $pdo = $connection->getPdo();
            $this->write($output, "-- SIMKLINIK MySQL database backup\n-- Created: " . now()->toIso8601String() . "\n\n");
            $this->write($output, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $tables = $connection->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']);
            foreach ($tables as $tableRow) {
                $table = array_values((array) $tableRow)[0] ?? null;
                if (!$table) {
                    continue;
                }

                $quotedTable = $this->quoteIdentifier((string) $table);
                $createRow = $connection->selectOne("SHOW CREATE TABLE {$quotedTable}");
                $create = array_values((array) $createRow)[1] ?? null;
                if (!$create) {
                    throw new RuntimeException("Could not read schema for table {$table}.");
                }

                $this->write($output, "DROP TABLE IF EXISTS {$quotedTable};\n{$create};\n\n");
                $columnRows = $connection->select("SHOW COLUMNS FROM {$quotedTable}");
                $columns = array_map(fn (object $column): string => $this->quoteIdentifier($column->Field), $columnRows);
                if (!$columns) {
                    continue;
                }

                $columnList = implode(', ', $columns);
                $statement = $pdo->query("SELECT * FROM {$quotedTable}");
                $batch = [];
                while ($row = $statement->fetch(\PDO::FETCH_NUM)) {
                    $values = array_map(fn ($value): string => $this->quoteValue($pdo, $value), $row);
                    $batch[] = '(' . implode(', ', $values) . ')';
                    if (count($batch) >= 200) {
                        $this->write($output, "INSERT INTO {$quotedTable} ({$columnList}) VALUES\n" . implode(",\n", $batch) . ";\n");
                        $batch = [];
                    }
                }
                $statement->closeCursor();
                if ($batch) {
                    $this->write($output, "INSERT INTO {$quotedTable} ({$columnList}) VALUES\n" . implode(",\n", $batch) . ";\n");
                }
                $this->write($output, "\n");
            }

            $this->write($output, "SET FOREIGN_KEY_CHECKS=1;\n");
            gzclose($output);
            @chmod($file, 0600);

            $backups = glob($directory . '/simklinik-*.sql.gz') ?: [];
            rsort($backups, SORT_STRING);
            foreach (array_slice($backups, $keep) as $oldBackup) {
                if (is_file($oldBackup)) {
                    unlink($oldBackup);
                }
            }

            $this->info('Database backup created successfully.');
            $this->line('File: ' . $file);
            $this->line('Size: ' . number_format(filesize($file) / 1024 / 1024, 2) . ' MB');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            gzclose($output);
            if (is_file($file)) {
                unlink($file);
            }
            report($exception);
            $this->error('Backup failed. Check the application log for details.');

            return self::FAILURE;
        }
    }

    private function write($stream, string $content): void
    {
        if (gzwrite($stream, $content) === false) {
            throw new RuntimeException('Could not write to the compressed backup.');
        }
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        $quoted = $pdo->quote((string) $value, \PDO::PARAM_STR);
        if ($quoted === false) {
            throw new RuntimeException('Could not encode a database value for backup.');
        }

        return $quoted;
    }
}
