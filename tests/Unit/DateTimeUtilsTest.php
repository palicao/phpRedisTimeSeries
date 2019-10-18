<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Unit;

use DateTimeImmutable;
use Palicao\PhpRedisTimeSeries\DateTimeUtils;
use PHPUnit\Framework\TestCase;

class DateTimeUtilsTest extends TestCase
{
    public function testDateTimeFromTimestampWithMsConvertsCorrectly() : void
    {
        $result = DateTimeUtils::dateTimeFromTimestampWithMs(1548149181000);
        $expected = new DateTimeImmutable('2019-01-22T09:26:21.000000');
        $this->assertEquals($expected, $result);
    }

    public function testTimestampWithMsFromDateTimeConvertsCorrectly() : void
    {
        $dateTime = new DateTimeImmutable('2019-01-22T09:26:21.000000');
        $result = DateTimeUtils::timestampWithMsFromDateTime($dateTime);
        $expected = 1548149181000;
        $this->assertEquals($expected, $result);
    }
}