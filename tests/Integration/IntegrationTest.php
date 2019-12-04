<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Integration;

use DateTimeImmutable;
use Palicao\PhpRedisTimeSeries\AggregationRule;
use Palicao\PhpRedisTimeSeries\Filter;
use Palicao\PhpRedisTimeSeries\Label;
use Palicao\PhpRedisTimeSeries\RedisClient;
use Palicao\PhpRedisTimeSeries\RedisConnectionParams;
use Palicao\PhpRedisTimeSeries\Sample;
use Palicao\PhpRedisTimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;
use Redis;

class IntegrationTest extends TestCase
{
    private $sut;

    public function setUp(): void
    {
        $host = getenv('REDIS_HOST') ?: 'redis';
        $port = getenv('REDIS_PORT') ? (int) getenv('REDIS_PORT') : 6379;
        $connectionParams = new RedisConnectionParams($host, $port);
        $redisClient = new RedisClient(new Redis(), $connectionParams);
        $redisClient->executeCommand(['FLUSHDB']);
        $this->sut = new TimeSeries($redisClient);
    }

    public function testAddAndRetrieveAsRange(): void
    {
        $from = new DateTimeImmutable('2019-11-06 20:34:17.103000');
        $to = new DateTimeImmutable('2019-11-06 20:34:17.107000');

        $this->sut->create(
            'temperature:3:11',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:11', 30, $from));
        $this->sut->add(new Sample('temperature:3:11', 42, $to));

        $range = $this->sut->range(
            'temperature:3:11',
            $from,
            $to,
            null,
            new AggregationRule(AggregationRule::AGG_AVG, 5)
        );

        $expectedRange = [
            new Sample('temperature:3:11', 30, new DateTimeImmutable('2019-11-06 20:34:17.100000')),
            new Sample('temperature:3:11', 42, new DateTimeImmutable('2019-11-06 20:34:17.105000'))
        ];

        $this->assertEquals($expectedRange, $range);
    }

    public function testAddAndRetrieveAsMRangeAndMultipleFilters(): void
    {
        $from = new DateTimeImmutable('2019-11-06 20:34:17.103000');
        $to = new DateTimeImmutable('2019-11-06 20:34:17.107000');

        $this->sut->create(
            'temperature:3:11',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:11', 30, $from));
        $this->sut->add(new Sample('temperature:3:11', 42, $to));

        $filter = new Filter('sensor_id', '2');
        $filter->add('area_id', Filter::OP_EQUALS, '32');

        $range = $this->sut->multiRange(
            $filter,
            null,
            null,
            null,
            null
        );

        $expectedRange = [
            new Sample('temperature:3:11', 30, new DateTimeImmutable('2019-11-06 20:34:17.103000')),
            new Sample('temperature:3:11', 42, new DateTimeImmutable('2019-11-06 20:34:17.107000'))
        ];

        $this->assertEquals($expectedRange, $range);
    }
}
