<?php

namespace App\Tests\Service;

use App\Contract\FilterConditionInterface;
use App\Contract\FilterInterface;
use App\Service\HistoryQuotesService;
use PHPUnit\Framework\TestCase;

class HistoryQuotesServiceTest extends TestCase
{
    private FilterInterface $filterMock;
    private HistoryQuotesService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filterMock = $this->createMock(FilterInterface::class);
        $this->service = new HistoryQuotesService($this->filterMock);
    }

    public function testConstructorAcceptsFilterInterface(): void
    {
        $filter = $this->createMock(FilterInterface::class);
        $service = new HistoryQuotesService($filter);

        $this->assertInstanceOf(HistoryQuotesService::class, $service);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(HistoryQuotesService::class);

        $this->assertTrue($reflection->isReadOnly());
    }

    public function testGetFilteredHistoryQuotesCallsFilterMethod(): void
    {
        $items = [
            ['id' => 1, 'symbol' => 'AAPL'],
            ['id' => 2, 'symbol' => 'GOOGL']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($items, $conditionsMock);

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);
    }

    public function testGetFilteredHistoryQuotesPassesItemsToFilter(): void
    {
        $items = [
            ['symbol' => 'AAPL', 'price' => 150],
            ['symbol' => 'GOOGL', 'price' => 2800]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($this->identicalTo($items), $this->anything());

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);
    }

    public function testGetFilteredHistoryQuotesPassesConditionsToFilter(): void
    {
        $items = [['id' => 1]];
        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($this->anything(), $this->identicalTo($conditionsMock));

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);
    }

    public function testGetFilteredHistoryQuotesReturnsFilterResult(): void
    {
        $items = [
            ['id' => 1, 'symbol' => 'AAPL'],
            ['id' => 2, 'symbol' => 'GOOGL']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $expectedResult = [
            ['id' => 1, 'symbol' => 'AAPL']
        ];

        $this->filterMock
            ->method('filter')
            ->willReturn($expectedResult);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetFilteredHistoryQuotesReturnsIterable(): void
    {
        $items = [['id' => 1]];
        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->method('filter')
            ->willReturn([]);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertIsIterable($result);
    }

    public function testGetFilteredHistoryQuotesWithEmptyItems(): void
    {
        $items = [];
        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($items, $conditionsMock)
            ->willReturn([]);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertIsIterable($result);
    }

    public function testGetFilteredHistoryQuotesReturnsEmptyWhenFilterReturnsEmpty(): void
    {
        $items = [
            ['id' => 1, 'symbol' => 'AAPL'],
            ['id' => 2, 'symbol' => 'GOOGL']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->method('filter')
            ->willReturn([]);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertEquals([], $result);
    }

    public function testGetFilteredHistoryQuotesWorksWithGenerator(): void
    {
        $generator = function() {
            yield ['id' => 1, 'symbol' => 'AAPL'];
            yield ['id' => 2, 'symbol' => 'GOOGL'];
        };

        $items = $generator();
        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($this->isInstanceOf(\Generator::class), $conditionsMock);

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);
    }

    public function testGetFilteredHistoryQuotesCanReturnGenerator(): void
    {
        $items = [['id' => 1]];
        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $generator = function() {
            yield ['id' => 1, 'filtered' => true];
        };

        $this->filterMock
            ->method('filter')
            ->willReturn($generator());

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertInstanceOf(\Generator::class, $result);
    }

    public function testGetFilteredHistoryQuotesWorksWithArrayIterator(): void
    {
        $items = new \ArrayIterator([
            ['id' => 1, 'symbol' => 'AAPL'],
            ['id' => 2, 'symbol' => 'GOOGL']
        ]);

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($this->isInstanceOf(\ArrayIterator::class), $conditionsMock);

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);
    }

    public function testGetFilteredHistoryQuotesWithComplexData(): void
    {
        $items = [
            [
                'id' => 1,
                'symbol' => 'AAPL',
                'start_date' => '2023-01-01',
                'end_date' => '2023-12-31',
                'open' => 150.0,
                'close' => 180.0,
                'volume' => 1000000
            ],
            [
                'id' => 2,
                'symbol' => 'GOOGL',
                'start_date' => '2023-01-01',
                'end_date' => '2023-12-31',
                'open' => 2800.0,
                'close' => 2900.0,
                'volume' => 500000
            ]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $filteredResult = [
            [
                'id' => 1,
                'symbol' => 'AAPL',
                'start_date' => '2023-01-01',
                'end_date' => '2023-12-31',
                'open' => 150.0,
                'close' => 180.0,
                'volume' => 1000000
            ]
        ];

        $this->filterMock
            ->method('filter')
            ->willReturn($filteredResult);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertEquals($filteredResult, $result);
    }

    public function testGetFilteredHistoryQuotesCanBeCalledMultipleTimes(): void
    {
        $items1 = [['id' => 1, 'symbol' => 'AAPL']];
        $items2 = [['id' => 2, 'symbol' => 'GOOGL']];

        $conditions1 = $this->createMock(FilterConditionInterface::class);
        $conditions2 = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->exactly(2))
            ->method('filter');

        $this->service->getFilteredHistoryQuotes($items1, $conditions1);
        $this->service->getFilteredHistoryQuotes($items2, $conditions2);

        $this->assertTrue(true);
    }

    public function testMultipleCallsWithDifferentResults(): void
    {
        $items1 = [['id' => 1]];
        $items2 = [['id' => 2]];

        $conditions = $this->createMock(FilterConditionInterface::class);

        $result1 = [['id' => 1, 'filtered' => true]];
        $result2 = [['id' => 2, 'filtered' => true]];

        $this->filterMock
            ->method('filter')
            ->willReturnOnConsecutiveCalls($result1, $result2);

        $actualResult1 = $this->service->getFilteredHistoryQuotes($items1, $conditions);
        $actualResult2 = $this->service->getFilteredHistoryQuotes($items2, $conditions);

        $this->assertEquals($result1, $actualResult1);
        $this->assertEquals($result2, $actualResult2);
    }

    public function testServiceDelegatesToFilterCompletely(): void
    {
        $items = [
            ['symbol' => 'AAPL', 'price' => 150],
            ['symbol' => 'GOOGL', 'price' => 2800],
            ['symbol' => 'MSFT', 'price' => 380]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $expectedResult = [
            ['symbol' => 'AAPL', 'price' => 150],
            ['symbol' => 'MSFT', 'price' => 380]
        ];

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($items, $conditionsMock)
            ->willReturn($expectedResult);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertEquals($expectedResult, $result);
    }

    public function testServiceDoesNotModifyItems(): void
    {
        $items = [
            ['id' => 1, 'symbol' => 'AAPL']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->method('filter')
            ->with($this->identicalTo($items), $this->anything())
            ->willReturn($items);

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertTrue(true);
    }

    public function testServiceDoesNotModifyConditions(): void
    {
        $items = [['id' => 1]];
        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->method('filter')
            ->with($this->anything(), $this->identicalTo($conditionsMock))
            ->willReturn([]);

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertTrue(true);
    }

    public function testGetFilteredHistoryQuotesReturnTypeIsIterable(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getFilteredHistoryQuotes');
        $returnType = $reflection->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertEquals('iterable', $returnType->getName());
    }

    public function testGetFilteredHistoryQuotesAcceptsIterableItems(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getFilteredHistoryQuotes');
        $parameters = $reflection->getParameters();

        $this->assertCount(2, $parameters);

        $itemsParam = $parameters[0];
        $this->assertEquals('items', $itemsParam->getName());
        $this->assertEquals('iterable', $itemsParam->getType()->getName());
    }

    public function testGetFilteredHistoryQuotesAcceptsFilterConditionInterface(): void
    {
        $reflection = new \ReflectionMethod($this->service, 'getFilteredHistoryQuotes');
        $parameters = $reflection->getParameters();

        $conditionsParam = $parameters[1];
        $this->assertEquals('conditions', $conditionsParam->getName());
        $this->assertEquals(FilterConditionInterface::class, $conditionsParam->getType()->getName());
    }

    public function testCompleteFilteringFlow(): void
    {
        $items = [
            ['id' => 1, 'symbol' => 'AAPL', 'date' => '2023-01-01'],
            ['id' => 2, 'symbol' => 'GOOGL', 'date' => '2023-01-02'],
            ['id' => 3, 'symbol' => 'AAPL', 'date' => '2023-01-03']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $filteredResult = [
            ['id' => 1, 'symbol' => 'AAPL', 'date' => '2023-01-01'],
            ['id' => 3, 'symbol' => 'AAPL', 'date' => '2023-01-03']
        ];

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($items, $conditionsMock)
            ->willReturn($filteredResult);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertEquals($filteredResult, $result);
        $this->assertCount(2, $result);
    }

    public function testGetFilteredHistoryQuotesWithSingleItem(): void
    {
        $items = [
            ['id' => 1, 'symbol' => 'AAPL']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($items, $conditionsMock)
            ->willReturn($items);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertEquals($items, $result);
    }

    public function testGetFilteredHistoryQuotesWithNestedArrays(): void
    {
        $items = [
            [
                'id' => 1,
                'symbol' => 'AAPL',
                'metadata' => [
                    'exchange' => 'NASDAQ',
                    'sector' => 'Technology'
                ]
            ]
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->method('filter')
            ->willReturn($items);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertEquals($items, $result);
    }

    public function testFilterMethodCalledExactlyOnce(): void
    {
        $items = [['id' => 1]];
        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter');

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);
    }

    public function testFilterMethodNeverCalledWithoutInvocation(): void
    {
        $this->filterMock
            ->expects($this->never())
            ->method('filter');

        $this->assertTrue(true);
    }

    public function testServiceIsImmutable(): void
    {
        $items1 = [['id' => 1]];
        $items2 = [['id' => 2]];

        $conditions = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->method('filter')
            ->willReturnArgument(0);

        $result1 = $this->service->getFilteredHistoryQuotes($items1, $conditions);
        $result2 = $this->service->getFilteredHistoryQuotes($items2, $conditions);

        $this->assertNotEquals($result1, $result2);
    }

    public function testGetFilteredHistoryQuotesWorksWithArrayObject(): void
    {
        $items = new \ArrayObject([
            ['id' => 1, 'symbol' => 'AAPL'],
            ['id' => 2, 'symbol' => 'GOOGL']
        ]);

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $this->filterMock
            ->expects($this->once())
            ->method('filter')
            ->with($this->isInstanceOf(\ArrayObject::class), $conditionsMock);

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertTrue(true);
    }

    public function testFilterCalledWithCorrectParameters(): void
    {
        $items = [
            ['id' => 1, 'symbol' => 'AAPL'],
            ['id' => 2, 'symbol' => 'GOOGL']
        ];

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $capturedItems = null;
        $capturedConditions = null;

        $this->filterMock
            ->method('filter')
            ->willReturnCallback(function($items, $conditions) use (&$capturedItems, &$capturedConditions) {
                $capturedItems = $items;
                $capturedConditions = $conditions;
                return [];
            });

        $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertSame($items, $capturedItems);
        $this->assertSame($conditionsMock, $capturedConditions);
    }

    public function testGetFilteredHistoryQuotesWithLargeDataset(): void
    {
        $items = array_map(function($i) {
            return ['id' => $i, 'symbol' => 'STOCK' . $i];
        }, range(1, 1000));

        $conditionsMock = $this->createMock(FilterConditionInterface::class);

        $filteredItems = array_slice($items, 0, 500);

        $this->filterMock
            ->method('filter')
            ->willReturn($filteredItems);

        $result = $this->service->getFilteredHistoryQuotes($items, $conditionsMock);

        $this->assertCount(500, $result);
    }
}
