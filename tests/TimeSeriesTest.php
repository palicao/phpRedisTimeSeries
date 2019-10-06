<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests;

use DateTimeImmutable;
use Palicao\PhpRedisTimeSeries\AggregationRule;
use Palicao\PhpRedisTimeSeries\Label;
use Palicao\PhpRedisTimeSeries\RedisClient;
use Palicao\PhpRedisTimeSeries\Sample;
use Palicao\PhpRedisTimeSeries\TimeSeries;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TimeSeriesTest extends TestCase
{
    /**
     * @var TimeSeries
     */
    private $sut;

    /**
     * @var RedisClient|MockObject
     */
    private $redisClientMock;

    public function setUp(): void
    {
        $this->redisClientMock = $this->createMock(RedisClient::class);
        $this->sut = new TimeSeries($this->redisClientMock);
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate($params, $expectedParams): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with($expectedParams);
        $this->sut->create(...$params);
    }

    public function createDataProvider()
    {
        return [
            [['a', 10, [new Label('l1', 'v1'), new Label('l2', 'v2')]], ['TS.CREATE', 'a', 'RETENTION', 10, 'LABELS', 'l1', 'v1', 'l2', 'v2']],
            [['a', 10], ['TS.CREATE', 'a', 'RETENTION', 10]],
            [['a'], ['TS.CREATE', 'a']],
        ];
    }

    public function testAlter(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.ALTER', 'a', 'RETENTION', 10, 'LABELS', 'l1', 'v1', 'l2', 'v2']);
        $this->sut->alter(
            'a',
            10,
            [new Label('l1', 'v1'), new Label('l2', 'v2')]
        );
    }

    /**
     * @dataProvider addDataProvider
     */
    public function testAdd($params, $expectedParams): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.ADD', 'a', '*', 10.1, 'RETENTION', 10, 'LABELS', 'l1', 'v1', 'l2', 'v2'])
            ->willReturn(1483300866234);
        $addedSample = $this->sut->add(
            new Sample('a', 10.1),
            10,
            [new Label('l1', 'v1'), new Label('l2', 'v2')]
        );
        $expectedSample = new Sample('a', 10.1, new DateTimeImmutable('2017-01-01T20.01.06.234'));
        $this->assertEquals($expectedSample, $addedSample);
    }

    public function addDataProvider()
    {
        return [[
            new Sample('a', 10.1), 10, [new Label('l1', 'v1'), new Label('l2', 'v2')],
            ['TS.ADD', 'a', '*', 10.1, 'RETENTION', 10, 'LABELS', 'l1', 'v1', 'l2', 'v2']
        ], [
            new Sample('a', 10.1, new DateTimeImmutable('2017-01-01T20.01.06.234')), 10, [new Label('l1', 'v1'), new Label('l2', 'v2')],
            ['TS.ADD', 'a', 1483300866234, 10.1, 'RETENTION', 10, 'LABELS', 'l1', 'v1', 'l2', 'v2']
        ]];
    }

    public function testAddMany(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.MADD', 'a', '*', 10.1, 'b', 1483300866234, 1.0])
            ->willReturn([1483300866233, 1483300866234]);
        $addedSamples = $this->sut->addMany([
            new Sample('a', 10.1),
            new Sample('b', 1, new DateTimeImmutable('2017-01-01T20.01.06.234'))
        ]);
        $expectedSamples = [
            new Sample('a', 10.1, new DateTimeImmutable('2017-01-01T20.01.06.233')),
            new Sample('b', 1.0, new DateTimeImmutable('2017-01-01T20.01.06.234'))
        ];
        $this->assertEquals($expectedSamples, $addedSamples);
    }

    public function testAddManyEmpty()
    {
        $this->redisClientMock
            ->expects($this->never())
            ->method('executeCommand')
            ->willReturn([]);
        $addedSamples = $this->sut->addMany([]);
        $this->assertEquals([], $addedSamples);
    }

    public function testIncrementBy(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.INCRBY', 'a', 10.1, 'RESET', 10, 'RETENTION', 20, 'LABELS', 'l1', 'v1', 'l2', 'v2']);
        $this->sut->incrementBy(
            new Sample('a', 10.1),
            10,
            20,
            [new Label('l1', 'v1'), new Label('l2', 'v2')]
        );
    }

    public function testDecrementBy(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.DECRBY', 'a', 10.1, 'RESET', 10, 'TIMESTAMP', 1483300866234, 'RETENTION', 20, 'LABELS', 'l1', 'v1', 'l2', 'v2'])
            ->willReturn(1483300866234);
        $this->sut->decrementBy(
            new Sample('a', 10.1, new DateTimeImmutable('2017-01-01T20.01.06.234')),
            10,
            20,
            [new Label('l1', 'v1'), new Label('l2', 'v2')]
        );
    }

    public function testCreateRule(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.CREATERULE', 'a', 'b', 'AGGREGATION', 'AVG', 100])
            ->willReturn(1483300866234);
        $this->sut->createRule('a', 'b', new AggregationRule(AggregationRule::AGG_AVG, 100));
    }

    public function testDeleteRule(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.DELETERULE', 'a', 'b'])
            ->willReturn(1483300866234);
        $this->sut->deleteRule('a', 'b');
    }

    public function testRange(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.RANGE', 'a', 1483300866234, 1522923630234, 'COUNT', 100, 'AGGREGATION', 'LAST', 200])
            ->willReturn([[1483300866234, 9.1], [1522923630234, 9.2]]);
        $returnedSamples = $this->sut->range(
            'a',
            new DateTimeImmutable('2017-01-01T20.01.06.234'),
            new DateTimeImmutable('2018-04-05T10.20.30.234'),
            100,
            new AggregationRule(AggregationRule::AGG_LAST, 200)
        );
        $expectedSamples = [
            new Sample('a', 9.1, new DateTimeImmutable('2017-01-01T20.01.06.234')),
            new Sample('a', 9.2, new DateTimeImmutable('2018-04-05T10.20.30.234'))
        ];

        $this->assertEquals($expectedSamples, $returnedSamples);
    }

    public function testRangeWithoutFrom(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.RANGE', 'a', '-', 1522923630234, 'COUNT', 100, 'AGGREGATION', 'LAST', 200])
            ->willReturn([]);
        $this->sut->range(
            'a',
            null,
            new DateTimeImmutable('2018-04-05T10.20.30.234'),
            100,
            new AggregationRule(AggregationRule::AGG_LAST, 200)
        );
    }

    public function testRangeWithoutFromAndTo(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.RANGE', 'a', '-', '+', 'COUNT', 100, 'AGGREGATION', 'LAST', 200])
            ->willReturn([]);
        $this->sut->range(
            'a',
            null,
            null,
            100,
            new AggregationRule(AggregationRule::AGG_LAST, 200)
        );
    }

    public function testRangeWithoutFromToAndCount(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.RANGE', 'a', '-', '+', 'AGGREGATION', 'LAST', 200])
            ->willReturn([]);
        $this->sut->range(
            'a',
            null,
            null,
            null,
            new AggregationRule(AggregationRule::AGG_LAST, 200)
        );
    }

    public function testRangeWithoutFromToCountAndAggregation(): void
    {
        $this->redisClientMock
            ->expects($this->once())
            ->method('executeCommand')
            ->with(['TS.RANGE', 'a', '-', '+'])
            ->willReturn([]);
        $this->sut->range(
            'a'
        );
    }


    public function testInfo(): void
    {

    }


    public function testGetLastValue(): void
    {

    }


    public function testGetLastValues(): void
    {

    }


    public function testMultiRange(): void
    {

    }



}
