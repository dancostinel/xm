<?php

namespace App\Tests\Service;

use App\Contract\FilterConditionInterface;
use App\Contract\FilterInterface;
use App\Service\DataFilterService;
use PHPUnit\Framework\TestCase;

class DataFilterServiceTest extends TestCase
{
    private DataFilterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new DataFilterService();
    }

    public function testImplementsFilterInterface(): void
    {
        $this->assertInstanceOf(FilterInterface::class, $this->service);
    }

    public function testFilterMethodExists(): void
    {
        $this->assertTrue(method_exists($this->service, 'filter'));
    }

    public function testFilterReturnsGenerator(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = $this->service->filter($items, $conditionsMock);

        $this->assertInstanceOf(\Generator::class, $result);
    }

    public function testFilterReturnsIterable(): void
    {
        $items = [['id' => 1]];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = $this->service->filter($items, $conditionsMock);

        $this->assertIsIterable($result);
    }

    public function testFilterCallsApplyFilterConditionsForEachItem(): void
    {
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->expects($this->exactly(3))
            ->method('applyFilterConditions')
            ->willReturn(true);

        iterator_to_array($this->service->filter($items, $conditionsMock));
    }

    public function testFilterPassesEachItemToConditions(): void
    {
        $items = [
            ['id' => 1, 'name' => 'First'],
            ['id' => 2, 'name' => 'Second']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->expects($this->exactly(2))
            ->method('applyFilterConditions')
            ->withConsecutive(
                [$this->equalTo(['id' => 1, 'name' => 'First'])],
                [$this->equalTo(['id' => 2, 'name' => 'Second'])]
            )
            ->willReturn(true);

        iterator_to_array($this->service->filter($items, $conditionsMock));
    }

    public function testFilterIncludesItemsWhenConditionReturnsTrue(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1, 'name' => 'Item 1'], $result[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Item 2'], $result[1]);
    }

    public function testFilterExcludesItemsWhenConditionReturnsFalse(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(false);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(0, $result);
        $this->assertEmpty($result);
    }

    public function testFilterIncludesOnlyMatchingItems(): void
    {
        $items = [
            ['id' => 1, 'include' => true],
            ['id' => 2, 'include' => false],
            ['id' => 3, 'include' => true],
            ['id' => 4, 'include' => false]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->method('applyFilterConditions')
            ->willReturnCallback(function ($item) {
                return $item['include'] === true;
            });

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1, 'include' => true], $result[0]);
        $this->assertEquals(['id' => 3, 'include' => true], $result[1]);
    }

    public function testFilterWithAlternatingConditions(): void
    {
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->method('applyFilterConditions')
            ->willReturnOnConsecutiveCalls(true, false, true, false);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result[0]);
        $this->assertEquals(['id' => 3], $result[1]);
    }

    public function testFilterWithEmptyArray(): void
    {
        $items = [];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->expects($this->never())
            ->method('applyFilterConditions');

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(0, $result);
        $this->assertEmpty($result);
    }

    public function testFilterWithNoMatchingItems(): void
    {
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(false);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertEmpty($result);
    }

    public function testFilterWithAllMatchingItems(): void
    {
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(3, $result);
    }

    public function testFilterWorksWithGenerator(): void
    {
        $generator = function() {
            yield ['id' => 1];
            yield ['id' => 2];
            yield ['id' => 3];
        };

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = iterator_to_array($this->service->filter($generator(), $conditionsMock));

        $this->assertCount(3, $result);
    }

    public function testFilterWorksWithArrayIterator(): void
    {
        $items = new \ArrayIterator([
            ['id' => 1],
            ['id' => 2]
        ]);

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(2, $result);
    }

    public function testFilterYieldsItemsOneByOne(): void
    {
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $count = 0;
        foreach ($this->service->filter($items, $conditionsMock) as $item) {
            $count++;
            $this->assertIsArray($item);
            $this->assertArrayHasKey('id', $item);
        }

        $this->assertEquals(3, $count);
    }

    public function testFilterWithComplexArrays(): void
    {
        $items = [
            [
                'id' => 1,
                'name' => 'Item 1',
                'metadata' => ['status' => 'active']
            ],
            [
                'id' => 2,
                'name' => 'Item 2',
                'metadata' => ['status' => 'inactive']
            ]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->method('applyFilterConditions')
            ->willReturnCallback(function ($item) {
                return $item['metadata']['status'] === 'active';
            });

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(1, $result);
        $this->assertEquals('active', $result[0]['metadata']['status']);
    }

    public function testFilterPreservesItemStructure(): void
    {
        $items = [
            [
                'id' => 1,
                'name' => 'Test',
                'nested' => ['value' => 100],
                'tags' => ['a', 'b', 'c']
            ]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertEquals($items[0], $result[0]);
        $this->assertArrayHasKey('nested', $result[0]);
        $this->assertArrayHasKey('tags', $result[0]);
    }

    public function testFilterWithSingleItem(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Only One']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 1, 'name' => 'Only One'], $result[0]);
    }

    public function testFilterWithSingleNonMatchingItem(): void
    {
        $items = [
            ['id' => 1]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(false);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertEmpty($result);
    }

    public function testFilterWithLargeDataset(): void
    {
        $items = array_map(function($i) {
            return ['id' => $i, 'even' => $i % 2 === 0];
        }, range(1, 1000));

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->method('applyFilterConditions')
            ->willReturnCallback(function ($item) {
                return $item['even'] === true;
            });

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(500, $result);
    }

    public function testFilterDoesNotLoadAllItemsIntoMemory(): void
    {
        $generator = function() {
            for ($i = 1; $i <= 100; $i++) {
                yield ['id' => $i];
            }
        };

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $memoryBefore = memory_get_usage();

        $count = 0;
        foreach ($this->service->filter($generator(), $conditionsMock) as $item) {
            $count++;
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        $this->assertEquals(100, $count);

        $this->assertLessThan(1024 * 1024, $memoryUsed);
    }

    public function testFilterPassesCorrectItemToCondition(): void
    {
        $specificItem = ['id' => 42, 'name' => 'Special'];
        $items = [$specificItem];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->expects($this->once())
            ->method('applyFilterConditions')
            ->with($this->identicalTo($specificItem))
            ->willReturn(true);

        iterator_to_array($this->service->filter($items, $conditionsMock));
    }

    public function testFilterHandlesConditionWithSideEffects(): void
    {
        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ];

        $processedIds = [];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock
            ->method('applyFilterConditions')
            ->willReturnCallback(function ($item) use (&$processedIds) {
                $processedIds[] = $item['id'];
                return $item['id'] % 2 === 1;
            });

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertEquals([1, 2, 3], $processedIds);

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1], $result[0]);
        $this->assertEquals(['id' => 3], $result[1]);
    }

    public function testFilterWithNullValues(): void
    {
        $items = [
            ['id' => 1, 'value' => null],
            ['id' => 2, 'value' => 'something']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $conditionsMock
            ->method('applyFilterConditions')
            ->willReturnCallback(function ($item) {
                return $item['value'] !== null;
            });

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(1, $result);
        $this->assertEquals(['id' => 2, 'value' => 'something'], $result[0]);
    }

    public function testFilterWithMixedDataTypes(): void
    {
        $items = [
            ['id' => 1, 'type' => 'string'],
            ['id' => 2, 'type' => 123],
            ['id' => 3, 'type' => true],
            ['id' => 4, 'type' => null]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock
            ->method('applyFilterConditions')
            ->willReturnCallback(function ($item) {
                return is_string($item['type']);
            });

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(1, $result);
    }

    public function testCanFilterMultipleTimes(): void
    {
        $items1 = [['id' => 1], ['id' => 2]];
        $items2 = [['id' => 3], ['id' => 4]];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result1 = iterator_to_array($this->service->filter($items1, $conditionsMock));
        $result2 = iterator_to_array($this->service->filter($items2, $conditionsMock));

        $this->assertCount(2, $result1);
        $this->assertCount(2, $result2);
        $this->assertNotEquals($result1, $result2);
    }

    public function testFilterWorksWithArrayObject(): void
    {
        $items = new \ArrayObject([
            ['id' => 1],
            ['id' => 2]
        ]);

        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = iterator_to_array($this->service->filter($items, $conditionsMock));

        $this->assertCount(2, $result);
    }

    public function testFilterWorksWithDifferentConditionImplementations(): void
    {
        $items = [
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'inactive']
        ];

        $activeCondition = $this->createMock(FilterConditionInterface::class);
        $activeCondition
            ->method('applyFilterConditions')
            ->willReturnCallback(fn($item) => $item['status'] === 'active');

        $inactiveCondition = $this->createMock(FilterConditionInterface::class);
        $inactiveCondition
            ->method('applyFilterConditions')
            ->willReturnCallback(fn($item) => $item['status'] === 'inactive');

        $activeResult = iterator_to_array($this->service->filter($items, $activeCondition));
        $inactiveResult = iterator_to_array($this->service->filter($items, $inactiveCondition));

        $this->assertCount(1, $activeResult);
        $this->assertEquals('active', $activeResult[0]['status']);

        $this->assertCount(1, $inactiveResult);
        $this->assertEquals('inactive', $inactiveResult[0]['status']);
    }

    public function testFilterReturnTypeIsIterable(): void
    {
        $items = [['id' => 1]];
        $conditionsMock = $this->createMock(FilterConditionInterface::class);
        $conditionsMock->method('applyFilterConditions')->willReturn(true);

        $result = $this->service->filter($items, $conditionsMock);

        $reflection = new \ReflectionMethod($this->service, 'filter');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('iterable', $returnType->getName());
    }
}
