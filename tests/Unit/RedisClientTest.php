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
        $redisMock->expects(self::once())->method('isConnected')->willReturn(false);
        $redisMock->expects(self::once())->method('connect')->with(
            '127.0.0.1',
            6379,
            3,
            null,
            1,
            2.2
        );
        $redisMock->expects(self::once())->method('rawCommand')->with('MY', 'command');
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
        $redisMock->expects(self::once())->method('isConnected')->willReturn(false);
        $redisMock->expects(self::once())->method('pconnect')->with(
            '127.0.0.1',
            6379,
            0,
            self::isType(IsType::TYPE_STRING),
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
        $redisMock->expects(self::once())->method('isConnected')->willReturn(true);
        $redisMock->expects(self::never())->method('connect');
        $redisMock->expects(self::never())->method('pconnect');
        $connectionParams = new RedisConnectionParams();
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }

    public function testFailureToConnectThrowsException(): void
    {
        $this->expectException(RedisClientException::class);
        $redisMock = $this->createMock(Redis::class);
        $redisMock->expects(self::once())->method('connect')->willReturn(false);
        $connectionParams = new RedisConnectionParams();
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }

    public function testConnectionWithPassword(): void
    {
        $redisMock = $this->createMock(Redis::class);
        $redisMock->expects(self::once())->method('isConnected')->willReturn(false);
        $redisMock->expects(self::once())->method('connect')->with(
            '127.0.0.1',
            6379,
            0,
            0,
            0.0
        );
        $redisMock->expects(self::once())
            ->method('auth')
            ->with('pass');
        $connectionParams = new RedisConnectionParams('127.0.0.1', 6379, null, 'pass');
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }

    public function testConnectionWithUseranameAndPassword(): void
    {
        $redisMock = $this->createMock(Redis::class);
        $redisMock->expects(self::once())->method('isConnected')->willReturn(false);
        $redisMock->expects(self::once())->method('connect')->with(
            '127.0.0.1',
            6379,
            0,
            0,
            0.0
        );
        $redisMock->expects(self::exactly(2))
            ->method('rawCommand')
            ->withConsecutive(
                ['AUTH', 'username', 'pass'],
                ['MY', 'command']
            );
        $connectionParams = new RedisConnectionParams('127.0.0.1', 6379, 'username', 'pass');
        $sut = new RedisClient($redisMock, $connectionParams);
        $sut->executeCommand(['MY', 'command']);
    }
}
