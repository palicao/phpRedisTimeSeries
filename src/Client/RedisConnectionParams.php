<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Client;

final class RedisConnectionParams
{
    /** @var bool */
    private $persistentConnection;

    /** @var string */
    private $host;

    /** @var int */
    private $port;

    /** @var int */
    private $timeout;

    /** @var int */
    private $retryInterval;

    /** @var float */
    private $readTimeout;

    /** @var string|null */
    private $username;

    /** @var string|null */
    private $password;

    /**
     * RedisConnectionParams constructor.
     * @param string $host
     * @param int $port
     * @param string|null $username
     * @param string|null $password
     */
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        ?string $username = null,
        ?string $password = null
    ) {
        $this->persistentConnection = false;
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->timeout = 0;
        $this->retryInterval = 0;
        $this->readTimeout = 0.0;
    }

    /**
     * Whether to use a persistent connection
     * @param bool $persistentConnection
     * @return RedisConnectionParams
     */
    public function setPersistentConnection(bool $persistentConnection): RedisConnectionParams
    {
        $this->persistentConnection = $persistentConnection;
        return $this;
    }

    /**
     * Connection timeout (in seconds)
     * @param int $timeout
     * @return RedisConnectionParams
     */
    public function setTimeout(int $timeout): RedisConnectionParams
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Retry interval (in seconds)
     * @param int $retryInterval
     * @return RedisConnectionParams
     */
    public function setRetryInterval(int $retryInterval): RedisConnectionParams
    {
        $this->retryInterval = $retryInterval;
        return $this;
    }

    /**
     * Read timeout in seconds
     * @param float $readTimeout
     * @return RedisConnectionParams
     */
    public function setReadTimeout(float $readTimeout): RedisConnectionParams
    {
        $this->readTimeout = $readTimeout;
        return $this;
    }

    public function isPersistentConnection(): bool
    {
        return $this->persistentConnection;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getRetryInterval(): int
    {
        return $this->retryInterval;
    }

    public function getReadTimeout(): float
    {
        return $this->readTimeout;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}
