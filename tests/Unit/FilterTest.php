<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Unit;

use Palicao\PhpRedisTimeSeries\Exception\InvalidFilterOperationException;
use Palicao\PhpRedisTimeSeries\Filter;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{

    public function testInvalidOperationThrowsException(): void
    {
        $this->expectException(InvalidFilterOperationException::class);
        $this->expectExceptionMessage('Operation is not valid');

        $filter = new Filter('a', 'b');
        $filter->add('a', 666);
    }

    /**
     * @param mixed $operation
     * @dataProvider stringOperations
     */
    public function testOperationRequiresString($operation): void
    {
        $this->expectException(InvalidFilterOperationException::class);
        $this->expectExceptionMessage('The provided operation requires the value to be string');

        $filter = new Filter('a', 'b');
        $filter->add('a', $operation, []);
    }

    /**
     * @param mixed $operation
     * @dataProvider nullOperations
     */
    public function testOperationRequiresNull($operation): void
    {
        $this->expectException(InvalidFilterOperationException::class);
        $this->expectExceptionMessage('The provided operation requires the value to be null');

        $filter = new Filter('a', 'b');
        $filter->add('a', $operation, []);
    }

    /**
     * @param mixed $operation
     * @dataProvider arrayOperations
     */
    public function testOperationRequiresArray($operation): void
    {
        $this->expectException(InvalidFilterOperationException::class);
        $this->expectExceptionMessage('The provided operation requires the value to be an array');

        $filter = new Filter('a', 'b');
        $filter->add('a', $operation, null);
    }

    public function testToRedisParams(): void
    {
        $filter = new Filter('lab1', 'val1');
        $filter->add('lab2', Filter::OP_NOT_EQUALS, 'val2');
        $filter->add('lab3', Filter::OP_EXISTS);
        $filter->add('lab4', Filter::OP_NOT_EXISTS);
        $filter->add('lab5', Filter::OP_IN, ['a', 'b', 'c']);
        $filter->add('lab6', Filter::OP_NOT_IN, ['d', 'e', 'f']);

        $result = $filter->toRedisParams();
        $expected = ['lab1=val1', 'lab2!=val2', 'lab3=', 'lab4!=', 'lab5=(a,b,c)', 'lab6!=(d,e,f)'];

        $this->assertEquals($expected, $result);
    }

    public function stringOperations(): array
    {
        return [[Filter::OP_EQUALS], [Filter::OP_NOT_EQUALS]];
    }

    public function nullOperations(): array
    {
        return [[Filter::OP_EXISTS], [Filter::OP_NOT_EXISTS]];
    }

    public function arrayOperations(): array
    {
        return [[Filter::OP_IN], [Filter::OP_NOT_IN]];
    }
}
