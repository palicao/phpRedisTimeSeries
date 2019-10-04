<?php

namespace Palicao\PhpRedisTimeSeries;

use DateTimeImmutable;
use DateTimeInterface;

class Sample
{
    /** @var string */
    private $key;

    /** @var float */
    private $value;

    /** @var DateTimeInterface|null */
    private $dateTime;

    public function __construct(string $key, float $value, ?DateTimeInterface $dateTime = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->dateTime = $dateTime;
    }

    public static function createFromTimestamp(string $key, float $value, int $timestamp) : Sample
    {
        $dateTime = DateTimeImmutable::createFromFormat('U.u', (string) $timestamp / 1000);
        return new self($key, $value, $dateTime);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getDateTime(): ?DateTimeInterface
    {
        return $this->dateTime;
    }

    /**
     * @return int|string
     */
    public function getTimestampWithMs()
    {
        if ($this->dateTime === null) {
            return '*';
        }
        return (int) round($this->dateTime->format('Uu') / 1000);
    }

    public function toRedisParams() : array
    {
        return [$this->getKey(), $this->getTimestampWithMs(), $this->getValue()];
    }
}
