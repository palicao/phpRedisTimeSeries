<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Unit;

use Palicao\PhpRedisTimeSeries\AggregationRule;
use Palicao\PhpRedisTimeSeries\Exception\InvalidAggregationException;
use PHPUnit\Framework\TestCase;

class AggregationRuleTest extends TestCase
{

    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(InvalidAggregationException::class);
        new AggregationRule('foo', 1000);
    }

    public function testTypesAreCaseInsensitive(): void
    {
        $rule = new AggregationRule('avg', 1000);
        $this->assertEquals(AggregationRule::AGG_AVG, $rule->getType());
    }
}
