<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Unit;

use DateTimeImmutable;
use Palicao\PhpRedisTimeSeries\Sample;
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    public function testGetTimestampWithMs(): void
    {
        $sample = new Sample('a', 1, new DateTimeImmutable('2017-01-01T20.01.06.234'));
        $ts = $sample->getTimestampWithMs();
        $this->assertEquals(1483300866234, $ts);
    }

    public function testCreateFromTimestamp(): void
    {
        $sample = Sample::createFromTimestamp('a', 1, 1483300866234);
        $dateTime = $sample->getDateTime();
        $this->assertEquals(new DateTimeImmutable('2017-01-01T20.01.06.234'), $dateTime);
    }

    public function testCurrentTimestampReturnsStar(): void
    {
        $sample = new Sample('a', 1);
        $params = $sample->toRedisParams();
        $this->assertEquals(['a', '*', 1], $params);
    }

    public function testToRedisParams(): void
    {
        $sample = Sample::createFromTimestamp('a', 1, 1483300866234);
        $params = $sample->toRedisParams();
        $this->assertEquals(['a', 1483300866234, 1], $params);
    }
}
