<?php

namespace App\Contract;

use App\Exception\FileReaderException;

interface FileReaderInterface
{
    /**
     * @throws FileReaderException
     */
    public function read(string $filePath): iterable;
}
