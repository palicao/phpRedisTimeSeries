<?php

namespace Palicao\PhpRedisTimeSeries;

class RedisConnectionParams
{
    /** @var bool */
    private $persistentConnection;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var int */
    private $timeout;

    /** @var float */
    private $retryInterval;

    /** @var float */
    private $readTimeout;

    /**
     * @param string $host
     * @param int $port
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
        $this->persistentConnection = false;
        $this->host = $host;
        $this->port = $port;
        $this->timeout = 0;
        $this->retryInterval = 0.0;
        $this->readTimeout = 0;
    }

    /**
     * @param bool $persistentConnection
     * @return RedisConnectionParams
     */
    public function setPersistentConnection(bool $persistentConnection): RedisConnectionParams
    {
        $this->persistentConnection = $persistentConnection;
        return $this;
    }

    /**
     * @param int $timeout
     * @return RedisConnectionParams
     */
    public function setTimeout(int $timeout): RedisConnectionParams
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @param float $retryInterval
     * @return RedisConnectionParams
     */
    public function setRetryInterval(float $retryInterval): RedisConnectionParams
    {
        $this->retryInterval = $retryInterval;
        return $this;
    }

    /**
     * @param float $readTimeout
     * @return RedisConnectionParams
     */
    public function setReadTimeout(float $readTimeout): RedisConnectionParams
    {
        $this->readTimeout = $readTimeout;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPersistentConnection(): bool
    {
        return $this->persistentConnection;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @return float
     */
    public function getRetryInterval(): float
    {
        return $this->retryInterval;
    }

    /**
     * @return float
     */
    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }
}
