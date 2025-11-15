<?php

namespace App\Service;

use App\Contract\FileReaderInterface;
use App\Exception\FileReaderException;

class JsonFileReaderService implements FileReaderInterface
{
    /**
     * Read JSON file line by line and yield each item
     * @throws FileReaderException
     */
    public function read(string $filePath): iterable
    {
        if (!file_exists($filePath)) {
            throw new FileReaderException("File not found: $filePath");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new FileReaderException("Cannot open file: $filePath");
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);

                // Skip empty lines, array brackets
                if (empty($trimmed) || $trimmed === '[' || $trimmed === ']') {
                    continue;
                }

                // Remove trailing comma from JSON object line
                $trimmed = rtrim($trimmed, ',');
                $trimmed = trim($trimmed, '[');
                $trimmed = trim($trimmed, ']');

                if (!empty($trimmed) && $trimmed[0] === '{') {
                    try {
                        yield json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
                    } catch (\JsonException $exception) {
                        // maybe log the error here
                        continue; // Skip invalid JSON lines
                    }
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
