<?php

namespace App\Service;

use App\Exception\CsvException;

class CsvService
{
    /**
     * @throws CsvException
     * Generate CSV file and save to disk
     */
    public function generateFile(iterable $data, string $filePath, array $headers = []): string
    {
        $directory = dirname($filePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $output = fopen($filePath, 'w');
        if (false === $output) {
            throw new CsvException("Cannot create file: $filePath");
        }

        if (!empty($headers)) {
            fputcsv($output, $headers);
        }

        $firstRow = true;
        foreach ($data as $item) {
            unset($item['symbol'], $item['company_name']);
            if ($firstRow && empty($headers)) {
                fputcsv($output, array_keys($item));
            }
            $firstRow = false;
            fputcsv($output, $item);
        }
        fclose($output);

        return $filePath;
    }
}
