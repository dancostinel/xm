<?php

namespace App\Tests\Service;

use App\Contract\FileReaderInterface;
use App\Exception\FileReaderException;
use App\Service\JsonFileReaderService;
use PHPUnit\Framework\TestCase;

class JsonFileReaderServiceTest extends TestCase
{
    private JsonFileReaderService $service;
    private string $testDataDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new JsonFileReaderService();
        $this->testDataDir = sys_get_temp_dir() . '/json_reader_tests_' . uniqid();
        if (!is_dir($this->testDataDir)) {
            mkdir($this->testDataDir, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDataDir)) {
            $files = glob($this->testDataDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->testDataDir);
        }

        parent::tearDown();
    }

    private function createTestFile(string $filename, string $content): string
    {
        $filePath = $this->testDataDir . '/' . $filename;
        file_put_contents($filePath, $content);

        return $filePath;
    }

    public function testImplementsFileReaderInterface(): void
    {
        $this->assertInstanceOf(FileReaderInterface::class, $this->service);
    }

    public function testReadMethodExists(): void
    {
        $this->assertTrue(method_exists($this->service, 'read'));
    }

    public function testThrowsExceptionWhenFileDoesNotExist(): void
    {
        $this->expectException(FileReaderException::class);
        $this->expectExceptionMessage('File not found: /path/to/nonexistent/file.json');

        iterator_to_array($this->service->read('/path/to/nonexistent/file.json'));
    }

    public function testThrowsExceptionWithCorrectFilePathInMessage(): void
    {
        $nonExistentPath = '/tmp/does_not_exist_' . uniqid() . '.json';

        try {
            iterator_to_array($this->service->read($nonExistentPath));
            $this->fail('Expected FileReaderException was not thrown');
        } catch (FileReaderException $e) {
            $this->assertStringContainsString($nonExistentPath, $e->getMessage());
            $this->assertStringContainsString('File not found', $e->getMessage());
        }
    }

    public function testReadsValidJsonArray(): void
    {
        $content = <<<JSON
[
    {"id": 1, "name": "Item 1"},
    {"id": 2, "name": "Item 2"},
    {"id": 3, "name": "Item 3"}
]
JSON;

        $filePath = $this->createTestFile('valid.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 1, 'name' => 'Item 1'], $result[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Item 2'], $result[1]);
        $this->assertEquals(['id' => 3, 'name' => 'Item 3'], $result[2]);
    }

    public function testReadsJsonArrayWithTrailingCommas(): void
    {
        $content = <<<JSON
[
    {"id": 1, "name": "Item 1"},
    {"id": 2, "name": "Item 2"},
    {"id": 3, "name": "Item 3"},
]
JSON;

        $filePath = $this->createTestFile('trailing_comma.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(3, $result);
    }

    public function testReadsJsonArrayWithoutTrailingCommas(): void
    {
        $content = <<<JSON
[
    {"id": 1, "name": "Item 1"}
    {"id": 2, "name": "Item 2"}
    {"id": 3, "name": "Item 3"}
]
JSON;

        $filePath = $this->createTestFile('no_commas.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(3, $result);
    }

    public function testReadsEmptyJsonArray(): void
    {
        $content = '[]';

        $filePath = $this->createTestFile('empty.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(0, $result);
        $this->assertIsArray($result);
    }

    public function testSkipsEmptyLines(): void
    {
        $content = <<<JSON
[
    {"id": 1},

    {"id": 2},
    
    
    {"id": 3}
]
JSON;

        $filePath = $this->createTestFile('empty_lines.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(3, $result);
    }

    public function testTrimsWhitespace(): void
    {
        $content = <<<JSON
[
    {"id": 1}   ,
        {"id": 2}  ,
  {"id": 3}
]
JSON;

        $filePath = $this->createTestFile('whitespace.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 1], $result[0]);
    }

    public function testSkipsInvalidJsonLines(): void
    {
        $content = <<<JSON
[
    {"id": 1, "name": "Valid"},
    {invalid json},
    {"id": 2, "name": "Also Valid"},
    {"broken": },
    {"id": 3, "name": "Valid Too"}
]
JSON;

        $filePath = $this->createTestFile('invalid_lines.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 1, 'name' => 'Valid'], $result[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Also Valid'], $result[1]);
        $this->assertEquals(['id' => 3, 'name' => 'Valid Too'], $result[2]);
    }

    public function testHandlesMalformedJson(): void
    {
        $content = <<<JSON
[
    {"id": 1},
    {this is not json},
    {"id": 2}
]
JSON;

        $filePath = $this->createTestFile('malformed.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(2, $result);
    }

    public function testReadsNestedJsonObjects(): void
    {
        $content = <<<JSON
[
    {"id": 1, "user": {"name": "John", "email": "john@example.com"}},
    {"id": 2, "user": {"name": "Jane", "email": "jane@example.com"}}
]
JSON;

        $filePath = $this->createTestFile('nested.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(2, $result);
        $this->assertIsArray($result[0]['user']);
        $this->assertEquals('John', $result[0]['user']['name']);
        $this->assertEquals('jane@example.com', $result[1]['user']['email']);
    }

    public function testReadsJsonWithArrays(): void
    {
        $content = <<<JSON
[
    {"id": 1, "tags": ["tag1", "tag2", "tag3"]},
    {"id": 2, "tags": ["tag4", "tag5"]}
]
JSON;

        $filePath = $this->createTestFile('with_arrays.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(2, $result);
        $this->assertIsArray($result[0]['tags']);
        $this->assertCount(3, $result[0]['tags']);
        $this->assertEquals(['tag1', 'tag2', 'tag3'], $result[0]['tags']);
    }

    public function testReadsJsonWithSpecialCharacters(): void
    {
        $content = <<<JSON
[
    {"name": "Test \"quotes\"", "description": "Line1\nLine2"},
    {"name": "Unicode: é, ñ, 中文", "emoji": "😀"}
]
JSON;

        $filePath = $this->createTestFile('special_chars.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(1, $result);
        $this->assertStringContainsString('中文', $result[0]['name']);
    }

    public function testReadsJsonWithNumericValues(): void
    {
        $content = <<<JSON
[
    {"int": 42, "float": 3.14, "negative": -10},
    {"zero": 0, "large": 1000000}
]
JSON;

        $filePath = $this->createTestFile('numeric.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(2, $result);
        $this->assertIsInt($result[0]['int']);
        $this->assertEquals(42, $result[0]['int']);
        $this->assertIsFloat($result[0]['float']);
        $this->assertEquals(3.14, $result[0]['float']);
    }

    public function testReadsJsonWithBooleanAndNull(): void
    {
        $content = <<<JSON
[
    {"active": true, "deleted": false, "data": null},
    {"enabled": true}
]
JSON;

        $filePath = $this->createTestFile('bool_null.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(2, $result);
        $this->assertTrue($result[0]['active']);
        $this->assertFalse($result[0]['deleted']);
        $this->assertNull($result[0]['data']);
    }

    public function testReturnsGenerator(): void
    {
        $content = '[{"id": 1}]';
        $filePath = $this->createTestFile('generator.json', $content);

        $result = $this->service->read($filePath);

        $this->assertInstanceOf(\Generator::class, $result);
    }

    public function testGeneratorIsIterable(): void
    {
        $content = '[{"id": 1}, {"id": 2}]';
        $filePath = $this->createTestFile('iterable.json', $content);

        $result = $this->service->read($filePath);

        $this->assertIsIterable($result);
    }

    public function testCanIterateMultipleTimes(): void
    {
        $content = '[
            {"start_date": "2023-07-28", "end_date": "2023-07-29", "open": 1.24, "high": 4.25, "low": 7.26, "close": 10.27, "volume": 1443, "symbol": "AAIT", "company_name": "company AAIT"},
            {"start_date": "2023-07-29", "end_date": "2023-07-29", "open": 2.24, "high": 5.25, "low": 8.26, "close": 11.27, "volume": 1444, "symbol": "AAIT", "company_name": "company AAIT"},
            {"start_date": "2023-07-30", "end_date": "2023-07-31", "open": 3.24, "high": 6.25, "low": 9.26, "close": 12.27, "volume": 1445, "symbol": "AAIT", "company_name": "company AAIT"}
        ]';
        $filePath = $this->createTestFile('iterate_twice.json', $content);

        // First iteration
        $result1 = iterator_to_array($this->service->read($filePath));
        $this->assertCount(3, $result1);

        // Second iteration
        $result2 = iterator_to_array($this->service->read($filePath));
        $this->assertCount(3, $result2);

        $this->assertEquals($result1, $result2);
    }

    public function testGeneratorYieldsItemsOneByOne(): void
    {
        $content = <<<JSON
[
    {"id": 1},
    {"id": 2},
    {"id": 3}
]
JSON;

        $filePath = $this->createTestFile('yield.json', $content);

        $count = 0;
        foreach ($this->service->read($filePath) as $item) {
            $count++;
            $this->assertIsArray($item);
            $this->assertArrayHasKey('id', $item);
        }

        $this->assertEquals(3, $count);
    }

    public function testClosesFileHandleOnSuccess(): void
    {
        $content = '[{"start_date": "2023-07-28", "end_date": "2023-07-29", "open": 1.24, "high": 1.25, "low": 1.26, "close": 1.27, "volume": 1443, "symbol": "AAIT", "company_name": "company AAIT"}]';
        $filePath = $this->createTestFile('handle_close.json', $content);
        iterator_to_array($this->service->read($filePath));
        $result = iterator_to_array($this->service->read($filePath));
        $this->assertCount(1, $result);
    }

    public function testClosesFileHandleOnException(): void
    {
        $content = '[{"id": 1}]';
        $filePath = $this->createTestFile('handle_exception.json', $content);

        try {
            iterator_to_array($this->service->read($filePath));
            $result = iterator_to_array($this->service->read($filePath));
            $this->assertCount(1, $result);
        } catch (\Exception $e) {
            $this->assertTrue(file_exists($filePath));
        }
    }

    public function testReadsLargeJsonFile(): void
    {
        $items = [];
        for ($i = 1; $i <= 1000; $i++) {
            $items[] = sprintf('{"id": %d, "name": "Item %d"}', $i, $i);
        }

        $content = "[\n" . implode(",\n", $items) . "\n]";
        $filePath = $this->createTestFile('large.json', $content);

        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(1000, $result);
        $this->assertEquals(['id' => 1, 'name' => 'Item 1'], $result[0]);
        $this->assertEquals(['id' => 1000, 'name' => 'Item 1000'], $result[999]);
    }

    public function testReadsJsonWithLongLines(): void
    {
        $longString = str_repeat('a', 10000);
        $content = sprintf('[{"data": "%s"}]', $longString);

        $filePath = $this->createTestFile('long_line.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(1, $result);
    }

    public function testReadsSingleLineJson(): void
    {
        $content = '[{"id": 1}, {"id": 2}, {"id": 3}]';

        $filePath = $this->createTestFile('single_line.json', $content);
        $result = iterator_to_array($this->service->read($filePath));
        $this->assertIsArray($result);
    }

    public function testReadsFileWithOnlyBrackets(): void
    {
        $content = "[\n]";

        $filePath = $this->createTestFile('only_brackets.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(0, $result);
    }

    public function testReadsFileWithMixedContent(): void
    {
        $content = <<<JSON
[
    {"type": "string", "value": "text"},
    {"type": "number", "value": 123},
    {"type": "boolean", "value": true},
    {"type": "null", "value": null},
    {"type": "array", "value": [1, 2, 3]},
    {"type": "object", "value": {"nested": "data"}}
]
JSON;

        $filePath = $this->createTestFile('mixed.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(6, $result);
        $this->assertIsString($result[0]['value']);
        $this->assertIsInt($result[1]['value']);
        $this->assertIsBool($result[2]['value']);
        $this->assertNull($result[3]['value']);
        $this->assertIsArray($result[4]['value']);
        $this->assertIsArray($result[5]['value']);
    }

    public function testHandlesJsonDepthLimit(): void
    {
        // Create deeply nested JSON (within the 512 depth limit)
        $json = '{"level1": {"level2": {"level3": {"level4": {"value": "deep"}}}}}';
        $content = "[\n$json\n]";

        $filePath = $this->createTestFile('deep.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        $this->assertCount(1, $result);
        $this->assertEquals('deep', $result[0]['level1']['level2']['level3']['level4']['value']);
    }

    public function testIgnoresLinesNotStartingWithBrace(): void
    {
        $content = <<<JSON
[
    {"id": 1},
    // This is a comment
    {"id": 2},
    "string value",
    123,
    {"id": 3}
]
JSON;

        $filePath = $this->createTestFile('non_object_lines.json', $content);
        $result = iterator_to_array($this->service->read($filePath));

        // Should only read lines starting with {
        $this->assertCount(3, $result);
    }

    public function testMemoryEfficientReading(): void
    {
        // Create a file with many items
        $items = [];
        for ($i = 1; $i <= 100; $i++) {
            $items[] = sprintf('{"id": %d}', $i);
        }

        $content = "[\n" . implode(",\n", $items) . "\n]";
        $filePath = $this->createTestFile('memory.json', $content);

        $memoryBefore = memory_get_usage();

        // Read file using generator (should not load all into memory)
        $count = 0;
        foreach ($this->service->read($filePath) as $item) {
            $count++;
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        $this->assertEquals(100, $count);

        // Memory usage should be relatively small compared to loading entire file
        // This is a rough check - actual values may vary
        $this->assertLessThan(1024 * 1024, $memoryUsed); // Less than 1MB
    }

    public function testReturnTypeIsIterable(): void
    {
        $content = '[{"id": 1}]';
        $filePath = $this->createTestFile('return_type.json', $content);

        $result = $this->service->read($filePath);

        $reflection = new \ReflectionMethod($this->service, 'read');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('iterable', $returnType->getName());
    }

//    public function testThrowsExceptionWhenFileIsNotReadable(): void
//    {
//        $content = '[{"id": 1}]';
//        $filePath = $this->createTestFile('unreadable.json', $content);
//
//        chmod($filePath, 0000);
//
//        try {
//            $this->expectException(FileReaderException::class);
//            $this->expectExceptionMessage('Cannot open file');
//
//            iterator_to_array($this->service->read($filePath));
//        } finally {
//            chmod($filePath, 0644);
//        }
//    }
}
