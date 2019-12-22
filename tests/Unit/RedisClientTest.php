<?php
/* @noinspection PhpUnhandledExceptionInspection */
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Tests\Unit;

use Palicao\PhpRedisTimeSeries\Exception\RedisClientException;
use Palicao\PhpRedisTimeSeries\Client\RedisClient;
use Palicao\PhpRedisTimeSeries\Client\RedisConnectionParams;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;
use Redis;

class RedisClientTest extends TestCase
{
    public function testExecuteCommand(): void
    {
        $redisMock = $this->createMock(Redis::class);
        $redisMock->expects($this->once())->method('isConnected')->willReturn(false);
        $redisMock->expects($this->once())->method('connect')->with(
            '127.0.0.1',
            6379,
            3,
            null,
            1,
            2.2
        );
        $redisMock->expects($this->once())->method('rawCommand')->with('MY', 'command');
        $connectionParams = new RedisConnectionParams();
        $connectionParams->setRetryInterval(1)
            ->setReadTimeout(2.2)
            ->setTimeout(3);
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }

    public function testPersistentConnection(): void
    {
        $redisMock = $this->createMock(Redis::class);
        $redisMock->expects($this->once())->method('isConnected')->willReturn(false);
        $redisMock->expects($this->once())->method('pconnect')->with(
            '127.0.0.1',
            6379,
            0,
            $this->isType(IsType::TYPE_STRING),
            0,
            0.0
        );
        $connectionParams = new RedisConnectionParams();
        $connectionParams->setPersistentConnection(true);
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }

    public function testDontConnectIfNotNecessary(): void
    {
        $redisMock = $this->createMock(Redis::class);
        $redisMock->expects($this->once())->method('isConnected')->willReturn(true);
        $redisMock->expects($this->never())->method('connect');
        $redisMock->expects($this->never())->method('pconnect');
        $connectionParams = new RedisConnectionParams();
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }

    public function testFailureToConnectThrowsException(): void
    {
        $this->expectException(RedisClientException::class);
        $redisMock = $this->createMock(Redis::class);
        $redisMock->expects($this->once())->method('connect')->willReturn(false);
        $connectionParams = new RedisConnectionParams();
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }
}
