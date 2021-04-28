<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

use DateTimeInterface;

/** @psalm-immutable */
class SampleWithLabels extends Sample
{
    /** @var Label[] */
    private $labels;

    /**
     * SampleWithLabels constructor.
     * @param string $key
     * @param float $value
     * @param DateTimeInterface|null $dateTime
     * @param Label[] $labels
     */
    public function __construct(string $key, float $value, ?DateTimeInterface $dateTime = null, array $labels = [])
    {
        parent::__construct($key, $value, $dateTime);
        $this->labels = $labels;
    }

    /**
     * @param string $key
     * @param float $value
     * @param int $timestamp
     * @param Label[] $labels
     * @return SampleWithLabels
     */
    public static function createFromTimestampAndLabels(
        string $key,
        float $value,
        int $timestamp,
        array $labels
    ): SampleWithLabels
    {
        return new self($key, $value, DateTimeUtils::dateTimeFromTimestampWithMs($timestamp), $labels);
    }

    /**
     * @return Label[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }
}
