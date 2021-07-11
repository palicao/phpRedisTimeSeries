<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

use DateTimeInterface;
use Palicao\PhpRedisTimeSeries\Client\RedisClientInterface;
use Palicao\PhpRedisTimeSeries\Exception\InvalidDuplicatePolicyException;
use Palicao\PhpRedisTimeSeries\Exception\RedisClientException;
use RedisException;

final class TimeSeries
{
    public const DUPLICATE_POLICY_BLOCK = 'BLOCK';
    public const DUPLICATE_POLICY_FIRST = 'FIRST';
    public const DUPLICATE_POLICY_LAST = 'LAST';
    public const DUPLICATE_POLICY_MIN = 'MIN';
    public const DUPLICATE_POLICY_MAX = 'MAX';
    public const DUPLICATE_POLICY_SUM = 'SUM';

    private const DUPLICATE_POLICIES = [
        self::DUPLICATE_POLICY_BLOCK,
        self::DUPLICATE_POLICY_FIRST,
        self::DUPLICATE_POLICY_LAST,
        self::DUPLICATE_POLICY_MIN,
        self::DUPLICATE_POLICY_MAX,
        self::DUPLICATE_POLICY_SUM
    ];


    /** @var RedisClientInterface */
    private $redis;

    /**
     * @param RedisClientInterface $redis
     */
    public function __construct(RedisClientInterface $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Creates a key
     * @see https://oss.redislabs.com/redistimeseries/commands/#tscreate
     * @param string $key
     * @param int|null $retentionMs
     * @param Label[] $labels
     * @param bool $uncompressed
     * @param int|null $chunkSize
     * @param string|null $duplicatePolicy
     * @return void
     * @throws RedisException
     */
    public function create(
        string $key,
        ?int $retentionMs = null,
        array $labels = [],
        bool $uncompressed = false,
        ?int $chunkSize = null,
        ?string $duplicatePolicy = null
    ): void
    {
        $params = [];

        if ($uncompressed === true) {
            $params[] = 'UNCOMPRESSED';
        }

        if ($chunkSize !== null) {
            $params[] = 'CHUNK_SIZE';
            $params[] = (string) $chunkSize;
        }

        if ($duplicatePolicy !== null) {
            if (!in_array($duplicatePolicy, self::DUPLICATE_POLICIES)) {
                throw new InvalidDuplicatePolicyException(sprintf("Duplicate policy %s is invalid", $duplicatePolicy));
            }
            $params[] = 'DUPLICATE_POLICY';
            $params[] = $duplicatePolicy;
        }

        $this->redis->executeCommand(array_merge(
            ['TS.CREATE', $key],
            $this->getRetentionParams($retentionMs),
            $params,
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
     * @param bool $uncompressed
     * @param int|null $chunkSize
     * @param string|null $duplicatePolicy
     * @return Sample
     * @throws RedisException
     */
    public function add(
        Sample $sample,
        ?int $retentionMs = null,
        array $labels = [],
        bool $uncompressed = false,
        ?int $chunkSize = null,
        ?string $duplicatePolicy = null
    ): Sample
    {
        $params = [];

        if ($uncompressed === true) {
            $params[] = 'UNCOMPRESSED';
        }

        if ($chunkSize !== null) {
            $params[] = 'CHUNK_SIZE';
            $params[] = (string) $chunkSize;
        }

        if ($duplicatePolicy !== null) {
            if (!in_array($duplicatePolicy, self::DUPLICATE_POLICIES)) {
                throw new InvalidDuplicatePolicyException(sprintf("Duplicate policy %s is invalid", $duplicatePolicy));
            }
            $params[] = 'ON_DUPLICATE';
            $params[] = $duplicatePolicy;
        }
        
        $timestamp = (int)$this->redis->executeCommand(array_merge(
            ['TS.ADD'],
            $sample->toRedisParams(),
            $params,
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
        /** @var int[] $timestamps */
        $timestamps = $this->redis->executeCommand($params);
        $count = count($timestamps);
        $results = [];
        for ($i = 0; $i < $count; $i++) {
            $results[] = Sample::createFromTimestamp(
                $samples[$i]->getKey(),
                $samples[$i]->getValue(),
                $timestamps[$i]
            );
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
        $params = [$op, $sample->getKey(), (string)$sample->getValue()];
        if ($resetMs !== null) {
            $params[] = 'RESET';
            $params[] = (string)$resetMs;
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
     * @param bool $reverse
     * @return Sample[]
     * @throws RedisClientException
     * @throws RedisException
     */
    public function range(
        string $key,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?int $count = null,
        ?AggregationRule $rule = null,
        bool $reverse = false
    ): array
    {
        $fromTs = $from ? (string)DateTimeUtils::timestampWithMsFromDateTime($from) : '-';
        $toTs = $to ? (string)DateTimeUtils::timestampWithMsFromDateTime($to) : '+';

        $command = $reverse ? 'TS.REVRANGE' : 'TS.RANGE';
        $params = [$command, $key, $fromTs, $toTs];
        if ($count !== null) {
            $params[] = 'COUNT';
            $params[] = (string)$count;
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
     * @param bool $reverse
     * @return Sample[]
     * @throws RedisClientException
     * @throws RedisException
     */
    public function multiRange(
        Filter $filter,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?int $count = null,
        ?AggregationRule $rule = null,
        bool $reverse = false
    ): array
    {
        $results = $this->multiRangeRaw($filter, $from, $to, $count, $rule, $reverse);

        $samples = [];
        foreach ($results as $groupByKey) {
            $key = $groupByKey[0];
            foreach ($groupByKey[2] as $result) {
                $samples[] = Sample::createFromTimestamp($key, (float)$result[1], (int)$result[0]);
            }
        }
        return $samples;
    }

    /**
     * @param Filter $filter
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param int|null $count
     * @param AggregationRule|null $rule
     * @param bool $reverse
     * @return SampleWithLabels[]
     * @throws RedisClientException
     * @throws RedisException
     */
    public function multiRangeWithLabels(
        Filter $filter,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?int $count = null,
        ?AggregationRule $rule = null,
        bool $reverse = false
    ): array
    {
        $results = $this->multiRangeRaw($filter, $from, $to, $count, $rule, $reverse, true);

        $samples = [];
        foreach ($results as $groupByKey) {
            $key = $groupByKey[0];
            $labels = [];
            foreach ($groupByKey[1] as $label) {
                $labels[] = new Label($label[0], $label[1]);
            }
            foreach ($groupByKey[2] as $result) {
                $samples[] = SampleWithLabels::createFromTimestampAndLabels(
                    $key,
                    (float)$result[1],
                    $result[0],
                    $labels
                );
            }
        }
        return $samples;
    }

    /**
     * @param Filter $filter
     * @param DateTimeInterface|null $from
     * @param DateTimeInterface|null $to
     * @param int|null $count
     * @param AggregationRule|null $rule
     * @param bool $reverse
     * @param bool $withLabels
     * @return array
     * @throws RedisException
     */
    private function multiRangeRaw(
        Filter $filter,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?int $count = null,
        ?AggregationRule $rule = null,
        bool $reverse = false,
        bool $withLabels = false
    ): array
    {
        $fromTs = $from ? (string)DateTimeUtils::timestampWithMsFromDateTime($from) : '-';
        $toTs = $to ? (string)DateTimeUtils::timestampWithMsFromDateTime($to) : '+';

        $command = $reverse ? 'TS.MREVRANGE' : 'TS.MRANGE';
        $params = [$command, $fromTs, $toTs];

        if ($count !== null) {
            $params[] = 'COUNT';
            $params[] = (string)$count;
        }

        $params = array_merge($params, $this->getAggregationParams($rule));

        if ($withLabels) {
            $params[] = 'WITHLABELS';
        }

        $params = array_merge($params, ['FILTER'], $filter->toRedisParams());

        return $this->redis->executeCommand($params);
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
        $results = $this->redis->executeCommand(
            array_merge(['TS.MGET', 'FILTER'], $filter->toRedisParams())
        );
        $samples = [];
        foreach ($results as $result) {
            // most recent versions of TS.MGET return results in a nested array
            if (count($result) === 3) {
                $samples[] = Sample::createFromTimestamp($result[0], (float)$result[2][1], (int)$result[2][0]);
            } else {
                $samples[] = Sample::createFromTimestamp($result[0], (float)$result[3], (int)$result[2]);
            }
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
        return $this->redis->executeCommand(
            array_merge(['TS.QUERYINDEX'], $filter->toRedisParams())
        );
    }

    /**
     * @param int|null $retentionMs
     * @return string[]
     */
    private function getRetentionParams(?int $retentionMs = null): array
    {
        if ($retentionMs === null) {
            return [];
        }
        return ['RETENTION', (string)$retentionMs];
    }

    /**
     * @param Label ...$labels
     * @return string[]
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

    /**
     * @param AggregationRule|null $rule
     * @return string[]
     */
    private function getAggregationParams(?AggregationRule $rule = null): array
    {
        if ($rule === null) {
            return [];
        }
        return ['AGGREGATION', $rule->getType(), (string)$rule->getTimeBucketMs()];
    }
}
