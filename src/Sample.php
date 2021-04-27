<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

use DateTimeInterface;

/** @psalm-immutable */
class Sample
{
    /** @var string */
    protected $key;

    /** @var float */
    protected $value;

    /** @var DateTimeInterface|null */
    protected $dateTime;

    public function __construct(string $key, float $value, ?DateTimeInterface $dateTime = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->dateTime = $dateTime;
    }

    public static function createFromTimestamp(string $key, float $value, int $timestamp): Sample
    {
        return new self($key, $value, DateTimeUtils::dateTimeFromTimestampWithMs($timestamp));
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
     * @return string
     * @psalm-external-mutation-free
     */
    public function getTimestampWithMs(): string
    {
        if ($this->dateTime === null) {
            return '*';
        }
        return (string)DateTimeUtils::timestampWithMsFromDateTime($this->dateTime);
    }

    /**
     * @return string[]
     */
    public function toRedisParams(): array
    {
        return [$this->getKey(), $this->getTimestampWithMs(), (string) $this->getValue()];
    }
}
