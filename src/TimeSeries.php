<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

use DateTimeInterface;
use RedisException;
use Palicao\PhpRedisTimeSeries\Exception\RedisClientException;

class TimeSeries
{
    /** @var RedisClient */
    private $redis;

    /**
     * @param RedisClient $redis
     */
    public function __construct(RedisClient $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Creates a key
     * @see https://oss.redislabs.com/redistimeseries/commands/#tscreate
     * @param string $key
     * @param int|null $retentionMs
     * @param Label[] $labels
     * @return void
     * @throws RedisClientException
     * @throws RedisException
     */
    public function create(string $key, ?int $retentionMs = null, array $labels = []): void
    {
        $this->redis->executeCommand(array_merge(
            ['TS.CREATE', $key],
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$labels)
        ));
    }

    /**
     * Modifies an existing key
     * @see https://oss.redislabs.com/redistimeseries/commands/#tsalter
     * @param string $key
     * @param int|null $retentionMs
     * @param Label[] $labels
     * @return void
     * @throws RedisClientException
     * @throws RedisException
     */
    public function alter(string $key, ?int $retentionMs = null, array $labels = []): void
    {
        $this->redis->executeCommand(array_merge(
            ['TS.ALTER', $key],
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$labels)
        ));
    }

    /**
     * Adds a sample
     * @see https://oss.redislabs.com/redistimeseries/commands/#tsadd
     * @param Sample $sample
     * @param int|null $retentionMs
     * @param Label[] $labels
     * @return Sample
     * @throws RedisClientException
     * @throws RedisException
     */
    public function add(Sample $sample, ?int $retentionMs = null, array $labels = []): Sample
    {
        $timestamp = (int)$this->redis->executeCommand(array_merge(
            ['TS.ADD'],
            $sample->toRedisParams(),
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$labels)
        ));
        return Sample::createFromTimestamp($sample->getKey(), $sample->getValue(), $timestamp);
    }

    /**
     * Adds many samples
     * @see https://oss.redislabs.com/redistimeseries/commands/#tsmadd
     * @param Sample[] $samples
     * @return Sample[]
     * @throws RedisClientException
     * @throws RedisException
     */
    public function addMany(array $samples): array
    {
        if (empty($samples)) {
            return [];
        }
        $params = ['TS.MADD'];
        foreach ($samples as $sample) {
            $sampleParams = $sample->toRedisParams();
            foreach ($sampleParams as $sampleParam) {
                $params[] = $sampleParam;
            }
        }
        $timestamps = $this->redis->executeCommand($params);
        $count = count($timestamps);
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = Sample::createFromTimestamp($samples[$i]->getKey(), $samples[$i]->getValue(), $timestamps[$i]);
        }
        return $results;
    }

    /**
     * Increments a sample by the amount given in the passed sample
     * @see https://oss.redislabs.com/redistimeseries/commands/#tsincrbytsdecrby
     * @param Sample $sample
     * @param int|null $resetMs
     * @param int|null $retentionMs
     * @param Label[] $labels
     * @throws RedisClientException
     * @throws RedisException
     */
    public function incrementBy(Sample $sample, ?int $resetMs = null, ?int $retentionMs = null, array $labels = []): void
    {
        $this->incrementOrDecrementBy('TS.INCRBY', $sample, $resetMs, $retentionMs, $labels);
    }

    /**
     * Decrements a sample by the amount given in the passed sample
     * @see https://oss.redislabs.com/redistimeseries/commands/#tsincrbytsdecrby
     * @param Sample $sample
     * @param int|null $resetMs
     * @param int|null $retentionMs
     * @param Label[] $labels
     * @throws RedisClientException
     * @throws RedisException
     */
    public function decrementBy(Sample $sample, ?int $resetMs = null, ?int $retentionMs = null, array $labels = []): void
    {
        $this->incrementOrDecrementBy('TS.DECRBY', $sample, $resetMs, $retentionMs, $labels);
    }

    /**
     * @param string $op
     * @param Sample $sample
     * @param int|null $resetMs
     * @param int|null $retentionMs
     * @param Label[] $labels
     * @return void
     * @throws RedisClientException
     * @throws RedisException
     */
    private function incrementOrDecrementBy(
        string $op,
        Sample $sample,
        ?int $resetMs = null,
        ?int $retentionMs = null,
        array $labels = []
    ): void
    {
        $params = [$op, $sample->getKey(), $sample->getValue()];
        if ($resetMs !== null) {
            $params[] = 'RESET';
            $params[] = $resetMs;
        }
        if ($sample->getDateTime() !== null) {
            $params[] = 'TIMESTAMP';
            $params[] = $sample->getTimestampWithMs();
        }
        $params = array_merge(
            $params,
            $this->getRetentionParams($retentionMs),
            $this->getLabelsParams(...$labels)
        );
        $this->redis->executeCommand($params);
    }

    /**
     * Creates an aggregation rules for a key
     * @see https://oss.redislabs.com/redistimeseries/commands/#tscreaterule
     * @param string $sourceKey
     * @param string $destKey
     * @param AggregationRule $rule
     * @return void
     * @throws RedisClientException
     * @throws RedisException
     */
    public function createRule(string $sourceKey, string $destKey, AggregationRule $rule): void
    {
        $this->redis->executeCommand(array_merge(
            ['TS.CREATERULE', $sourceKey, $destKey],
            $this->getAggregationParams($rule)
        ));
    }

    /**
     * Deletes an existing aggregation rule
     * @see https://oss.redislabs.com/redistimeseries/commands/#tsdeleterule
     * @param string $sourceKey
     * @param string $destKey
     * @return void
     * @throws RedisClientException
     * @throws RedisException
     */
    public function deleteRule(string $sourceKey, string $destKey): void
    {
        $this->redis->executeCommand(['TS.DELETERULE', $sourceKey, $destKey]);
    }

    /**
     * Gets samples for a key, optionally aggregating them
     * @see https://oss.redislabs.com/redistimeseries/commands/#tsrange
     * @param string $key
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param int|null $count
     * @param AggregationRule|null $rule
     * @return Sample[]
     * @throws RedisClientException
     * @throws RedisException
     */
    public function range(
        string $key,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?int $count = null,
        ?AggregationRule $rule = null
    ): array
    {
        $fromTs = $from ? (int)$from->format('Uu') / 1000 : '-';
        $toTs = $to ? (int)$to->format('Uu') / 1000 : '+';

        $params = ['TS.RANGE', $key, $fromTs, $toTs];
        if ($count !== null) {
            $params[] = 'COUNT';
            $params[] = $count;
        }

        $rawResults = $this->redis->executeCommand(array_merge($params, $this->getAggregationParams($rule)));

        $samples = [];
        foreach ($rawResults as $rawResult) {
            $samples[] = Sample::createFromTimestamp($key, (float)$rawResult[1], (int)$rawResult[0]);
        }
        return $samples;
    }

    /**
     * Gets samples from multiple keys, searching by a given filter.
     * @param Filter $filter
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param int|null $count
     * @param AggregationRule|null $rule
     * @return Sample[]
     * @throws RedisClientException
     * @throws RedisException
     */
    public function multiRange(
        Filter $filter,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?int $count = null,
        ?AggregationRule $rule = null
    ): array
    {
        $results = $this->multiRangeRaw($filter, $from, $to, $count, $rule);

        $samples = [];
        foreach ($results as $groupByKey) {
            $key = $groupByKey[0];
            foreach ($groupByKey[2] as $result) {
                $samples[] = Sample::createFromTimestamp($key, (float)$result[1], (int)$result[0]);
            }
        }
        return $samples;
    }

    public function multiRangeRaw(Filter $filter,
          ?DateTimeInterface $from = null,
          ?DateTimeInterface $to = null,
          ?int $count = null,
          ?AggregationRule $rule = null
    ): array
    {
        $fromTs = $from ? (int)$from->format('Uu') / 1000 : '-';
        $toTs = $to ? (int)$to->format('Uu') / 1000 : '+';

        $params = ['TS.MRANGE', $fromTs, $toTs];
        if ($count !== null) {
            $params[] = 'COUNT';
            $params[] = $count;
        }

        return $this->redis->executeCommand(array_merge(
            $params,
            $this->getAggregationParams($rule),
            ['FILTER'],
            $filter->toRedisParams()
        ));
    }

    /**
     * Gets the last sample for a key
     * @param string $key
     * @return Sample
     * @throws RedisClientException
     * @throws RedisException
     */
    public function getLastSample(string $key): Sample
    {
        $result = $this->redis->executeCommand(['TS.GET', $key]);
        return Sample::createFromTimestamp($key, (float)$result[1], (int)$result[0]);
    }

    /**
     * Gets the last samples for multiple keys using a filter
     * @param Filter $filter
     * @return array
     * @throws RedisClientException
     * @throws RedisException
     */
    public function getLastSamples(Filter $filter): array
    {
        $results = $this->redis->executeCommand(['TS.MGET', 'FILTER', $filter->toRedisParams()]);
        $samples = [];
        foreach ($results as $result) {
            $samples[] = Sample::createFromTimestamp($result[0], (float)$result[3], (int)$result[2]);
        }
        return $samples;
    }

    /**
     * Gets metadata regarding a key
     * @param string $key
     * @return Metadata
     * @throws RedisException
     */
    public function info(string $key): Metadata
    {
        $result = $this->redis->executeCommand(['TS.INFO', $key]);

        $labels = [];
        foreach ($result[9] as $strLabel) {
            $labels[] = new Label($strLabel[0], $strLabel[1]);
        }

        $sourceKey = $result[11] === false ? null : $result[11];

        $rules = [];
        foreach ($result[13] as $rule) {
            $rules[$rule[0]] = new AggregationRule($rule[2], $rule[1]);
        }

        return Metadata::fromRedis($result[1], $result[3], $result[5], $result[7], $labels, $sourceKey, $rules);
    }

    /**
     * Lists the keys matching a filter
     * @param Filter $filter
     * @return string[]
     * @throws RedisException
     */
    public function getKeysByFilter(Filter $filter): array
    {
        return $this->redis->executeCommand(['TS.QUERYINDEX', $filter->toRedisParams()]);
    }

    private function getRetentionParams(?int $retentionMs = null): array
    {
        if ($retentionMs === null) {
            return [];
        }
        return ['RETENTION', $retentionMs];
    }

    /**
     * @param Label ...$labels
     * @return array
     */
    private function getLabelsParams(Label ...$labels): array
    {
        $params = [];
        foreach ($labels as $label) {
            $params[] = $label->getKey();
            $params[] = $label->getValue();
        }

        if (empty($params)) {
            return [];
        }

        array_unshift($params, 'LABELS');
        return $params;
    }

    private function getAggregationParams(?AggregationRule $rule = null): array
    {
        if ($rule === null) {
            return [];
        }
        return ['AGGREGATION', $rule->getType(), $rule->getTimeBucketMs()];
    }
}
