<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Integration;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Palicao\PhpRedisTimeSeries\AggregationRule;
use Palicao\PhpRedisTimeSeries\Client\RedisClient;
use Palicao\PhpRedisTimeSeries\Client\RedisConnectionParams;
use Palicao\PhpRedisTimeSeries\DateTimeUtils;
use Palicao\PhpRedisTimeSeries\Filter;
use Palicao\PhpRedisTimeSeries\Label;
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
        $from = new DateTimeImmutable('2019-11-06 20:34:17.000');
        $to = new DateTimeImmutable('2019-11-06 20:34:17.100');

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
            new AggregationRule(AggregationRule::AGG_AVG, 10)
        );

        $expectedRange = [
            new Sample('temperature:3:11', 30, new DateTimeImmutable('2019-11-06 20:34:17.000')),
            new Sample('temperature:3:11', 42, new DateTimeImmutable('2019-11-06 20:34:17.100'))
        ];

        $this->assertEquals($expectedRange, $range);
    }

    public function testAddAndRetrieveAsMultiRangeWithMultipleFilters(): void
    {
        $from = new DateTimeImmutable('2019-11-06 20:34:17.000');
        $to = new DateTimeImmutable('2019-11-06 20:34:17.100');

        $this->sut->create(
            'temperature:3:11',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:11', 30, $from));
        $this->sut->add(new Sample('temperature:3:11', 42, $to));

        $filter = new Filter('sensor_id', '2');
        $filter->add('area_id', Filter::OP_EQUALS, '32');

        $range = $this->sut->multiRange($filter);

        $expectedRange = [
            new Sample('temperature:3:11', 30, new DateTimeImmutable('2019-11-06 20:34:17.000')),
            new Sample('temperature:3:11', 42, new DateTimeImmutable('2019-11-06 20:34:17.100'))
        ];

        $this->assertEquals($expectedRange, $range);
    }

    public function testAddAndRetrieveAsLastSamplesWithMultipleFilters(): void
    {
        $from = new DateTimeImmutable('2019-11-06 20:34:17.000');
        $to = new DateTimeImmutable('2019-11-06 20:34:18.000');

        $this->sut->create(
            'temperature:3:11',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:11', 30, $from));
        $this->sut->add(new Sample('temperature:3:11', 42, $to));

        $this->sut->create(
            'temperature:3:12',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:12', 30, $from));
        $this->sut->add(new Sample('temperature:3:12', 42, $to));

        $filter = new Filter('sensor_id', '2');
        $filter->add('area_id', Filter::OP_EQUALS, '32');

        $range = $this->sut->getLastSamples($filter);

        $expectedResult = [
            new Sample('temperature:3:11', 42, new DateTimeImmutable('2019-11-06 20:34:18.000')),
            new Sample('temperature:3:12', 42, new DateTimeImmutable('2019-11-06 20:34:18.000'))
        ];

        $this->assertEquals($expectedResult, $range);
    }

    public function testAddAndRetrieveKeysWithMultipleFilters(): void
    {
        $from = new DateTimeImmutable('2019-11-06 20:34:17.000');
        $to = new DateTimeImmutable('2019-11-06 20:34:17.100');

        $this->sut->create(
            'temperature:3:11',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:11', 30, $from));
        $this->sut->add(new Sample('temperature:3:11', 42, $to));

        $this->sut->create(
            'temperature:3:12',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:12', 30, $from));
        $this->sut->add(new Sample('temperature:3:12', 42, $to));

        $filter = new Filter('sensor_id', '2');
        $filter->add('area_id', Filter::OP_EQUALS, '32');

        $range = $this->sut->getKeysByFilter($filter);

        $expectedResult = ['temperature:3:11', 'temperature:3:12'];

        $this->assertEquals($expectedResult, $range);
    }

    public function testAddAndRetrieveWithDateTimeObjectAsMultiRangeWithMultipleFilters(): void
    {
        $currentDate = new DateTime();
        $from = (clone $currentDate)->sub(new DateInterval('P1D'));
        $to = $currentDate;

        $this->sut->create(
            'temperature:3:11',
            6000,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );
        $this->sut->add(new Sample('temperature:3:11', 30, $from));
        $this->sut->add(new Sample('temperature:3:11', 42, $to));

        $filter = new Filter('sensor_id', '2');
        $filter->add('area_id', Filter::OP_EQUALS, '32');

        $range = $this->sut->multiRange($filter);

        $expectedRange = [
            Sample::createFromTimestamp('temperature:3:11', (float)42, DateTimeUtils::timestampWithMsFromDateTime(new DateTimeImmutable($to->format('Y-m-d H:i:s.u'))))
        ];

        $this->assertEquals($expectedRange, $range);
    }


    public function testAddAndRetrieveWithDateTimeObjectAsRange(): void
    {
        $from = new DateTimeImmutable('2019-11-06 20:34:17.103000');
        $to = new DateTimeImmutable('2019-11-06 20:34:17.107000');

        $this->sut->create(
            'temperature:3:11',
            null,
            [new Label('sensor_id', '2'), new Label('area_id', '32')]
        );

        $this->sut->add(new Sample('temperature:3:11', 30, $from));
        $this->sut->add(new Sample('temperature:3:11', 42, $to));

        $range = $this->sut->range(
            'temperature:3:11'
        );

        $expectedRange = [
            Sample::createFromTimestamp(
                'temperature:3:11',
                (float)30,
                DateTimeUtils::timestampWithMsFromDateTime(new DateTimeImmutable($from->format('Y-m-d H:i:s.u')))
            ),
            Sample::createFromTimestamp(
                'temperature:3:11',
                (float)42,
                DateTimeUtils::timestampWithMsFromDateTime(new DateTimeImmutable($to->format('Y-m-d H:i:s.u')))
            ),
        ];

        $this->assertEquals($expectedRange, $range);
    }
}
