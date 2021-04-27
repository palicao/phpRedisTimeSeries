<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

use DateTimeInterface;

/** @psalm-immutable */
final class Metadata
{
    /** @var DateTimeInterface */
    private $lastTimestamp;

    /** @var int */
    private $retentionTime;

    /** @var int */
    private $chunkCount;

    /** @var int */
    private $maxSamplesPerChunk;

    /** @var Label[] */
    private $labels;

    /** @var string|null */
    private $sourceKey;

    /** @var AggregationRule[] */
    private $rules;

    /**
     * @param DateTimeInterface $lastTimestamp
     * @param int $retentionTime
     * @param int $chunkCount
     * @param int $maxSamplesPerChunk
     * @param Label[] $labels
     * @param string|null $sourceKey
     * @param AggregationRule[] $rules
     */
    public function __construct(
        DateTimeInterface $lastTimestamp,
        int $retentionTime = 0,
        int $chunkCount = 0,
        int $maxSamplesPerChunk = 0,
        array $labels = [],
        ?string $sourceKey = null,
        array $rules = []
    ) {
        $this->lastTimestamp = $lastTimestamp;
        $this->retentionTime = $retentionTime;
        $this->chunkCount = $chunkCount;
        $this->maxSamplesPerChunk = $maxSamplesPerChunk;
        $this->labels = $labels;
        $this->sourceKey = $sourceKey;
        $this->rules = $rules;
    }

    /**
     * @param int $lastTimestamp
     * @param int $retentionTime
     * @param int $chunkCount
     * @param int $maxSamplesPerChunk
     * @param Label[] $labels
     * @param string|null $sourceKey
     * @param AggregationRule[] $rules
     * @return static
     */
    public static function fromRedis(
        int $lastTimestamp,
        int $retentionTime = 0,
        int $chunkCount = 0,
        int $maxSamplesPerChunk = 0,
        array $labels = [],
        ?string $sourceKey = null,
        array $rules = []
    ): self
    {
        $dateTime = DateTimeUtils::dateTimeFromTimestampWithMs($lastTimestamp);
        return new self($dateTime, $retentionTime, $chunkCount, $maxSamplesPerChunk, $labels, $sourceKey, $rules);
    }

    /**
     * @return DateTimeInterface
     */
    public function getLastTimestamp(): DateTimeInterface
    {
        return $this->lastTimestamp;
    }

    /**
     * @return int
     */
    public function getRetentionTime(): int
    {
        return $this->retentionTime;
    }

    /**
     * @return int
     */
    public function getChunkCount(): int
    {
        return $this->chunkCount;
    }

    /**
     * @return int
     */
    public function getMaxSamplesPerChunk(): int
    {
        return $this->maxSamplesPerChunk;
    }

    /**
     * @return Label[]
     */
    public function getLabels(): array
    {
        return $this->labels;
    }

    /**
     * @return string
     */
    public function getSourceKey(): ?string
    {
        return $this->sourceKey;
    }

    /**
     * @return AggregationRule[]
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
