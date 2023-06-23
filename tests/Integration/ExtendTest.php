<?php
/* @noinspection PhpUnhandledExceptionInspection */

declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Integration;

use Palicao\PhpRedisTimeSeries\Client\RedisClient;
use Palicao\PhpRedisTimeSeries\Client\RedisConnectionParams;
use Palicao\PhpRedisTimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;
use Redis;

class MyTimeSeries extends TimeSeries
{
    public function getRedis()
    {
        return $this->redis;
    }
}

class ExtendTest extends TestCase
{
    private $redisClient;
    private $sut;

    public function setUp(): void
    {
        $host = getenv('REDIS_HOST') ?: 'php-rts-redis';
        $port = getenv('REDIS_PORT') ? (int) getenv('REDIS_PORT') : 6379;
        $connectionParams = new RedisConnectionParams($host, $port);
        $this->redisClient = new RedisClient(new Redis(), $connectionParams);
        $this->sut = new MyTimeSeries($this->redisClient);
    }

    public function testRedisPropertyScope(): void
    {
        self::assertSame($this->redisClient, $this->sut->getRedis());
    }
}
