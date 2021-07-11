<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Unit;

use DateTimeImmutable;
use Palicao\PhpRedisTimeSeries\Label;
use Palicao\PhpRedisTimeSeries\SampleWithLabels;
use PHPUnit\Framework\TestCase;

class SampleWithLabelsTest extends TestCase
{
    public function testLabelsCanBeRetrieved(): void
    {
        $sample = new SampleWithLabels(
            'a', 
            1,
            new DateTimeImmutable('2017-01-01T20.01.06.234'),
            [new Label('a','10'), new Label('b', '20')]
        );
        
        self::assertEquals([new Label('a','10'), new Label('b', '20')], $sample->getLabels());
    }
}
