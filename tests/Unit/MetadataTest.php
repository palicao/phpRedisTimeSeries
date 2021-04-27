<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Unit;

use DateTimeImmutable;
use Palicao\PhpRedisTimeSeries\AggregationRule;
use Palicao\PhpRedisTimeSeries\Label;
use Palicao\PhpRedisTimeSeries\Metadata;
use PHPUnit\Framework\TestCase;

class MetadataTest extends TestCase
{
    public function testFromRedis(): void
    {
        $labels = [new Label('a', 'b')];
        $rules =  [new AggregationRule(AggregationRule::AGG_AVG, 4)];
        $metadata = Metadata::fromRedis(
            1483300866234,
            1,
            2,
            3,
            $labels,
            null,
            $rules
        );
        self::assertEquals(new DateTimeImmutable('2017-01-01T20.01.06.234'), $metadata->getLastTimestamp());
        self::assertEquals(1, $metadata->getRetentionTime());
        self::assertEquals(2, $metadata->getChunkCount());
        self::assertEquals(3, $metadata->getMaxSamplesPerChunk());
        self::assertEquals($labels, $metadata->getLabels());
        self::assertEquals(null, $metadata->getSourceKey());
        self::assertEquals($rules, $metadata->getRules());
    }
}
