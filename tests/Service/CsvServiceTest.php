<?php

namespace App\Tests\Service;

use App\Exception\CsvException;
use App\Service\CsvService;
use PHPUnit\Framework\TestCase;

class CsvServiceTest extends TestCase
{
    private CsvService $service;
    private string $testDataDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CsvService();
        $this->testDataDir = sys_get_temp_dir() . '/csv_service_tests_' . uniqid();
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDataDir)) {
            $this->removeDirectory($this->testDataDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function getCsvContent(string $filePath): array
    {
        $content = [];
        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return [];
        }

        while (($row = fgetcsv($handle)) !== false) {
            $content[] = $row;
        }

        fclose($handle);
        return $content;
    }

    public function testGenerateFileCreatesFile(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ];

        $filePath = $this->testDataDir . '/test.csv';

        $result = $this->service->generateFile($data, $filePath);

        $this->assertFileExists($filePath);
        $this->assertEquals($filePath, $result);
    }

    public function testGenerateFileWithHeaders(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ];

        $headers = ['ID', 'Name'];
        $filePath = $this->testDataDir . '/with_headers.csv';

        $this->service->generateFile($data, $filePath, $headers);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['ID', 'Name'], $content[0]);
        $this->assertEquals(['1', 'Item 1'], $content[1]);
        $this->assertEquals(['2', 'Item 2'], $content[2]);
    }

    public function testGenerateFileWithoutHeaders(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ];

        $filePath = $this->testDataDir . '/no_headers.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['id', 'name'], $content[0]);
        $this->assertEquals(['1', 'Item 1'], $content[1]);
        $this->assertEquals(['2', 'Item 2'], $content[2]);
    }

    public function testGenerateFileReturnsFilePath(): void
    {
        $data = [['id' => 1]];
        $filePath = $this->testDataDir . '/return_path.csv';

        $result = $this->service->generateFile($data, $filePath);

        $this->assertEquals($filePath, $result);
        $this->assertIsString($result);
    }

    public function testRemovesSymbolField(): void
    {
        $data = [
            ['id' => 1, 'symbol' => 'AAPL', 'name' => 'Apple'],
            ['id' => 2, 'symbol' => 'GOOGL', 'name' => 'Google']
        ];

        $filePath = $this->testDataDir . '/no_symbol.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['id', 'name'], $content[0]);
        $this->assertEquals(['1', 'Apple'], $content[1]);
        $this->assertEquals(['2', 'Google'], $content[2]);
    }

    public function testRemovesCompanyNameField(): void
    {
        $data = [
            ['id' => 1, 'company_name' => 'Apple Inc.', 'value' => 100],
            ['id' => 2, 'company_name' => 'Google LLC', 'value' => 200]
        ];

        $filePath = $this->testDataDir . '/no_company.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['id', 'value'], $content[0]);
        $this->assertEquals(['1', '100'], $content[1]);
        $this->assertEquals(['2', '200'], $content[2]);
    }

    public function testRemovesBothSymbolAndCompanyName(): void
    {
        $data = [
            ['id' => 1, 'symbol' => 'AAPL', 'company_name' => 'Apple Inc.', 'price' => 150]
        ];

        $filePath = $this->testDataDir . '/no_both.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['id', 'price'], $content[0]);
        $this->assertEquals(['1', '150'], $content[1]);
    }

    public function testCreatesDirectoryIfNotExists(): void
    {
        $nestedPath = $this->testDataDir . '/level1/level2/level3';
        $filePath = $nestedPath . '/test.csv';

        $data = [['id' => 1]];

        $this->assertDirectoryDoesNotExist($nestedPath);

        $this->service->generateFile($data, $filePath);

        $this->assertDirectoryExists($nestedPath);
        $this->assertFileExists($filePath);
    }

    public function testCreatesMultipleNestedDirectories(): void
    {
        $deepPath = $this->testDataDir . '/a/b/c/d/e/f';
        $filePath = $deepPath . '/deep.csv';

        $data = [['id' => 1]];

        $this->service->generateFile($data, $filePath);

        $this->assertDirectoryExists($deepPath);
        $this->assertFileExists($filePath);
    }

    public function testWorksWithExistingDirectory(): void
    {
        $subDir = $this->testDataDir . '/existing';
        mkdir($subDir, 0777, true);

        $filePath = $subDir . '/existing_dir.csv';
        $data = [['id' => 1]];

        $this->service->generateFile($data, $filePath);

        $this->assertFileExists($filePath);
    }

    // ========== Exception Tests ==========

//    public function testThrowsExceptionWhenCannotCreateFile(): void
//    {
//        // Create directory with no write permissions
//        $readOnlyDir = $this->testDataDir . '/readonly';
//        mkdir($readOnlyDir, 0777, true);
//        chmod($readOnlyDir, 0444); // Read-only
//
//        $filePath = $readOnlyDir . '/readonly.csv';
//        $data = [['id' => 1]];
//
//        try {
//            $this->expectException(CsvException::class);
//            $this->expectExceptionMessage('Cannot create file');
//
//            $this->service->generateFile($data, $filePath);
//        } finally {
//            // Restore permissions for cleanup
//            chmod($readOnlyDir, 0777);
//        }
//    }

//    public function testExceptionMessageContainsFilePath(): void
//    {
//        $readOnlyDir = $this->testDataDir . '/noaccess';
//        mkdir($readOnlyDir, 0777, true);
//        chmod($readOnlyDir, 0444);
//
//        $filePath = $readOnlyDir . '/test_path.csv';
//        $data = [['id' => 1]];
//
//        try {
//            $this->service->generateFile($data, $filePath);
//            $this->fail('Expected CsvException was not thrown');
//        } catch (CsvException $e) {
//            $this->assertStringContainsString($filePath, $e->getMessage());
//        } finally {
//            chmod($readOnlyDir, 0777);
//        }
//    }

    public function testGenerateFileWithEmptyData(): void
    {
        $data = [];
        $filePath = $this->testDataDir . '/empty.csv';

        $this->service->generateFile($data, $filePath);

        $this->assertFileExists($filePath);

        $content = $this->getCsvContent($filePath);
        $this->assertEmpty($content);
    }

    public function testGenerateFileWithEmptyDataAndHeaders(): void
    {
        $data = [];
        $headers = ['ID', 'Name'];
        $filePath = $this->testDataDir . '/empty_with_headers.csv';

        $this->service->generateFile($data, $filePath, $headers);

        $content = $this->getCsvContent($filePath);

        $this->assertCount(1, $content);
        $this->assertEquals(['ID', 'Name'], $content[0]);
    }

    public function testHandlesNumericValues(): void
    {
        $data = [
            ['int' => 42, 'float' => 3.14, 'negative' => -10],
            ['int' => 0, 'float' => 0.0, 'negative' => -99]
        ];

        $filePath = $this->testDataDir . '/numeric.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['42', '3.14', '-10'], $content[1]);
        $this->assertEquals(['0', '0', '-99'], $content[2]);
    }

    public function testHandlesBooleanValues(): void
    {
        $data = [
            ['active' => true, 'deleted' => false],
            ['active' => false, 'deleted' => true]
        ];

        $filePath = $this->testDataDir . '/boolean.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['1', ''], $content[1]);
        $this->assertEquals(['', '1'], $content[2]);
    }

    public function testHandlesNullValues(): void
    {
        $data = [
            ['id' => 1, 'value' => null],
            ['id' => 2, 'value' => null]
        ];

        $filePath = $this->testDataDir . '/null.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['1', ''], $content[1]);
        $this->assertEquals(['2', ''], $content[2]);
    }

    public function testHandlesSpecialCharacters(): void
    {
        $data = [
            ['text' => 'Text with "quotes"', 'comma' => 'Value, with comma'],
            ['text' => "Line1\nLine2", 'comma' => 'Normal']
        ];

        $filePath = $this->testDataDir . '/special.csv';

        $this->service->generateFile($data, $filePath);

        $this->assertFileExists($filePath);

        $content = $this->getCsvContent($filePath);
        $this->assertCount(3, $content);
    }

    public function testHandlesUnicodeCharacters(): void
    {
        $data = [
            ['name' => 'José', 'description' => '中文'],
            ['name' => 'Müller', 'description' => 'Ñoño']
        ];

        $filePath = $this->testDataDir . '/unicode.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertStringContainsString('José', $content[1][0]);
        $this->assertStringContainsString('中文', $content[1][1]);
    }

    public function testWorksWithGenerator(): void
    {
        $generator = function() {
            yield ['id' => 1, 'name' => 'Item 1'];
            yield ['id' => 2, 'name' => 'Item 2'];
            yield ['id' => 3, 'name' => 'Item 3'];
        };

        $filePath = $this->testDataDir . '/generator.csv';

        $this->service->generateFile($generator(), $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertCount(4, $content);
        $this->assertEquals(['id', 'name'], $content[0]);
    }

    public function testWorksWithArrayIterator(): void
    {
        $data = new \ArrayIterator([
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ]);

        $filePath = $this->testDataDir . '/iterator.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertCount(3, $content);
    }

    public function testHandlesLargeDataset(): void
    {
        $generator = function() {
            for ($i = 1; $i <= 1000; $i++) {
                yield ['id' => $i, 'value' => $i * 10];
            }
        };

        $filePath = $this->testDataDir . '/large.csv';

        $this->service->generateFile($generator(), $filePath);

        $this->assertFileExists($filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertCount(1001, $content);
        $this->assertEquals(['id', 'value'], $content[0]);
        $this->assertEquals(['1000', '10000'], $content[1000]);
    }

    public function testOverwritesExistingFile(): void
    {
        $filePath = $this->testDataDir . '/overwrite.csv';

        file_put_contents($filePath, "old content");

        $data = [['id' => 1, 'name' => 'New']];

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['id', 'name'], $content[0]);
        $this->assertEquals(['1', 'New'], $content[1]);
    }

    public function testSymbolRemovalDoesNotAffectOtherFields(): void
    {
        $data = [
            [
                'id' => 1,
                'symbol' => 'AAPL',
                'company_name' => 'Apple',
                'price' => 150,
                'volume' => 1000
            ]
        ];

        $filePath = $this->testDataDir . '/preserve_fields.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['id', 'price', 'volume'], $content[0]);
        $this->assertEquals(['1', '150', '1000'], $content[1]);
    }

    public function testRemovesSymbolEvenIfNull(): void
    {
        $data = [
            ['id' => 1, 'symbol' => null, 'company_name' => null, 'value' => 100]
        ];

        $filePath = $this->testDataDir . '/null_removal.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['id', 'value'], $content[0]);
    }

    public function testAutoGeneratesHeadersOnlyOnce(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
            ['id' => 3, 'name' => 'Item 3']
        ];

        $filePath = $this->testDataDir . '/auto_header_once.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertCount(4, $content);
        $this->assertEquals(['id', 'name'], $content[0]);
    }

    public function testNoAutoHeadersWhenHeadersProvided(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Item 1']
        ];

        $headers = ['ID', 'Name'];
        $filePath = $this->testDataDir . '/provided_headers.csv';

        $this->service->generateFile($data, $filePath, $headers);

        $content = $this->getCsvContent($filePath);

        $this->assertEquals(['ID', 'Name'], $content[0]);
        $this->assertNotEquals(['id', 'name'], $content[0]);
    }

    public function testHandlesSingleItem(): void
    {
        $data = [['id' => 1, 'name' => 'Only One']];
        $filePath = $this->testDataDir . '/single.csv';

        $this->service->generateFile($data, $filePath);

        $content = $this->getCsvContent($filePath);

        $this->assertCount(2, $content);
        $this->assertEquals(['id', 'name'], $content[0]);
        $this->assertEquals(['1', 'Only One'], $content[1]);
    }
}
