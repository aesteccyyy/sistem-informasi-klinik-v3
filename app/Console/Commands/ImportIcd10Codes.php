<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
use RuntimeException;
use Throwable;

class ImportIcd10Codes extends Command
{
    protected $signature = 'icd10:import
        {file : XLSX or CSV file with CODE and DISPLAY/DESCRIPTION columns}
        {--catalog-version= : Catalogue version to record for imported codes}';

    protected $description = 'Import an official ICD-10 catalogue from an XLSX or CSV file';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (!is_file($path) || !is_readable($path)) {
            $this->error('Spreadsheet file does not exist or is not readable.');

            return self::FAILURE;
        }

        $reader = null;

        try {
            $reader = ReaderFactory::createFromFile($path);
            $reader->open($path);
            $sheets = $reader->getSheetIterator();
            if (!$sheets->valid()) {
                throw new RuntimeException('Spreadsheet does not contain a sheet.');
            }

            $catalogVersion = $this->option('catalog-version');
            $batch = [];
            $imported = 0;
            $headerIndexes = null;

            foreach ($sheets->current()->getRowIterator() as $spreadsheetRow) {
                $row = $this->normalizeRow($spreadsheetRow->getCells());

                if ($headerIndexes === null) {
                    $headers = array_map('strtolower', $row);
                    $codeIndex = array_search('code', $headers, true);
                    $descriptionIndex = array_search('description', $headers, true);
                    if ($descriptionIndex === false) {
                        $descriptionIndex = array_search('display', $headers, true);
                    }

                    if ($codeIndex === false || $descriptionIndex === false) {
                        throw new RuntimeException('Spreadsheet must contain CODE and DISPLAY or DESCRIPTION headers.');
                    }

                    $headerIndexes = [
                        'code' => $codeIndex,
                        'description' => $descriptionIndex,
                        'version' => array_search('version', $headers, true),
                    ];

                    continue;
                }

                $codeIndex = $headerIndexes['code'];
                $descriptionIndex = $headerIndexes['description'];
                $versionIndex = $headerIndexes['version'];

                if (!isset($row[$codeIndex], $row[$descriptionIndex])) {
                    continue;
                }

                $code = strtoupper(trim((string) $row[$codeIndex]));
                $description = trim((string) $row[$descriptionIndex]);
                if ($code === '' || $description === '') {
                    continue;
                }

                $rowVersion = $versionIndex !== false
                    ? trim((string) ($row[$versionIndex] ?? ''))
                    : null;

                $now = now();
                $batch[] = [
                    'code' => $code,
                    'description' => $description,
                    'version' => $catalogVersion ?: ($rowVersion ?: null),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= 500) {
                    $this->upsert($batch);
                    $imported += count($batch);
                    $batch = [];
                }
            }

            if ($headerIndexes === null) {
                throw new RuntimeException('Spreadsheet does not contain a header row.');
            }

            if ($batch !== []) {
                $this->upsert($batch);
                $imported += count($batch);
            }

            $this->info("Imported or updated {$imported} ICD-10 codes.");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            $reader?->close();
        }
    }

    private function normalizeRow(array $cells): array
    {
        $values = array_map(fn ($cell): string => trim((string) $cell->getValue()), $cells);

        if (count($values) === 1 && str_contains($values[0], ',')) {
            $values = str_getcsv($values[0], ',', '"', '\\');
        }

        return array_map(
            fn ($value): string => trim(ltrim((string) $value, "\xEF\xBB\xBF")),
            $values,
        );
    }

    private function upsert(array $rows): void
    {
        DB::table('icd10_codes')->upsert(
            $rows,
            ['code'],
            ['description', 'version', 'updated_at'],
        );
    }
}