# phpRedisTimeSeries

Use [Redis Time Series](https://oss.redislabs.com/redistimeseries/) in PHP!

[![Maintainability](https://api.codeclimate.com/v1/badges/fea927b90378dd63a9d8/maintainability)](https://codeclimate.com/github/palicao/phpRedisTimeSeries/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/fea927b90378dd63a9d8/test_coverage)](https://codeclimate.com/github/palicao/phpRedisTimeSeries/test_coverage)
[![Build Status](https://github.com/palicao/phpRedisTimeSeries/actions/workflows/main.yml/badge.svg)](https://travis-ci.com/palicao/phpRedisTimeSeries)
[![Latest Stable Version](https://img.shields.io/packagist/v/palicao/php-redis-time-series.svg)](https://packagist.org/packages/palicao/php-redis-time-series)
![PHP version](https://img.shields.io/packagist/php-v/palicao/php-redis-time-series/2.0.0)
![GitHub](https://img.shields.io/github/license/palicao/phpRedisTimeSeries)

## Getting up and running

### Install
`composer require palicao/php-redis-time-series`

### Requirements
The library is tested against:
- PHP 7.2, 7.3, 7.4, 8.0
- RedisTimeSeries 1.4.10 (but it should work with any 1.4 version)

In order to use RedisTimeSeries 1.2 please use version 2.1.1 of this library.

### Construct
```
$ts = new TimeSeries(
    new RedisClient(
        new Redis(),
        new RedisConnectionParams($host, $port)
    )
);
```

## `TimeSeries` methods

### `create`

`TimeSeries::create(string $key, ?int $retentionMs = null, array $labels = []): void`

Creates a key, optionally setting a retention time (in milliseconds) and some labels.

See https://oss.redislabs.com/redistimeseries/commands/#tscreate.

### `alter`

`TimeSeries::alter(string $key, ?int $retentionMs = null, array $labels = []): void`
 
Modifies an existing key's retention time and/or labels.

See https://oss.redislabs.com/redistimeseries/commands/#tsalter.
 
### `add`
 
`TimeSeries::add(Sample $sample, ?int $retentionMs = null, array $labels = []): Sample`
  
Adds a sample.

If the key was not explicitly `create`d, it's possible to set retention and labels.

See https://oss.redislabs.com/redistimeseries/commands/#tsadd.

Examples: 
* `$sample = $ts->add(new Sample('myKey', 32.10), 10, [new Label('myLabel', 'myValue')]);` Adds a sample to `myKey` at 
the current timestamp (time of the redis server) and returns it (complete with dateTime).
* `$sample = $ts->add(new Sample('myKey', 32.10, new DateTimeImmutable()), 10, [new Label('myLabel', 'myValue')]);`
Adds a sample to `myKey` at a given datetime and returns it.

Please notice that RedisTimeSeries only allows to add samples in order (no sample older than the latest is allowed)

### `addMany`

`TimeSeries::addMany(array $samples): array`

Adds several samples.

As usual, if no timestamp is provided, the redis server current time is used. Added samples are returned in an array.

See https://oss.redislabs.com/redistimeseries/commands/#tsmadd.

### `incrementBy` and `decrementBy`

`TimeSeries::incrementBy(Sample $sample, ?int $resetMs = null, ?int $retentionMs = null, array $labels = []): void`

`TimeSeries::decrementBy(Sample $sample, ?int $resetMs = null, ?int $retentionMs = null, array $labels = []): void`

Add a sample to a key, incrementing or decrementing the last value by an amount specified in `$sample`.
The value can be optionally reset after `$resetMs` milliseconds.

Similarly to `add`, if the command is used on a non-existing key, it's possible to set retention and labels. 

See https://oss.redislabs.com/redistimeseries/commands/#tsincrbytsdecrby.

### `createRule`

`TimeSeries::createRule(string $sourceKey, string $destKey, AggregationRule $rule): void`

Creates an aggregation rule which applies the given `$rule` to `$sourceKey` in order to populate `$destKey`.

Notice that both key must already exist otherwise the command will fail.

See https://oss.redislabs.com/redistimeseries/commands/#tscreaterule.

Example:

* `$ts->createRule('source', 'destination', new AggregationRule(AggregationRule::AVG, 10000)`, will populate the
`destination` key by averaging the samples contained in `source` over 10 second buckets. 

### `deleteRule`

`TimeSeries::deleteRule(string $sourceKey, string $destKey): void`

Deletes an existing aggregation rule.

See https://oss.redislabs.com/redistimeseries/commands/#tsdeleterule.

### `range`

`TimeSeries::range(string $key, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null, ?int $count = null, ?AggregationRule $rule = null, bool $reverse = false): Sample[]`

Retrieves samples from a key. It's possible to limit the query in a given time frame (passing `$from` and `$to`),
to limit the retrieved amount of samples (passing `$count`), and also to pre-aggregate the results using a `$rule`.

The flag `$reverse` will return the results in reverse time order.

See https://oss.redislabs.com/redistimeseries/commands/#tsrange.

### `multiRange` and `multiRangeWithLabels`

`TimeSeries::multiRange(Filter $filter, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null, ?int $count = null, ?AggregationRule $rule = null, bool $reverse = false): Sample[]`

`TimeSeries::multiRangeWithLabels(Filter $filter, ?DateTimeInterface $from = null, ?DateTimeInterface $to = null, ?int $count = null, ?AggregationRule $rule = null, bool $reverse = false): SampleWithLabels[]`

Similar to `range`, but instead of querying by key, queries for a specific set of labels specified in `$filter`.

`multiRangeWithLabels` will return an array of `SampleWithLabels` instead of simple `Sample`s.

The flag `$reverse` will return the results in reverse time order.

See https://oss.redislabs.com/redistimeseries/commands/#tsmrange.

### `getLastSample`

`TimeSeries::getLastSample(string $key): Sample`

Gets the last sample for a key.

See https://oss.redislabs.com/redistimeseries/commands/#tsget.

### `getLastSamples`

`TimeSeries::getLastSamples(Filter $filter): Sample[]`

Gets the last sample for a set of keys matching a given `$filter`.

See https://oss.redislabs.com/redistimeseries/commands/#tsmget.

### `info`

`TimeSeries::info(string $key): Metadata`

Gets useful metadata for a given key.

See https://oss.redislabs.com/redistimeseries/commands/#tsinfo.

### `getKeysByFilter`

`getKeysByFilter(Filter $filter): string[]`

Retrieves the list of keys matching a given `$filter`.

See https://oss.redislabs.com/redistimeseries/commands/#tsqueryindex.

## Advanced connection parameters

You can manipulate your `RedisConnectionParams` using the following methods:

* `RedisConnectionParams::setPersistentConnection(bool $persistentConnection)` enable/disable a persistent connection
* `RedisConnectionParams::setTimeout(int $timeout)` connection timeout in seconds
* `RedisConnectionParams::setRetryInterval(int $retryInterval)` retry interval in seconds
* `RedisConnectionParams::setReadTimeout(float $readTimeout)` read timeout in seconds

## Building Filters

A `Filter` is composed of multiple filtering operations. At least one equality operation must be provided (in the 
constructor). Adding filtering operations can be chained using the method `add`.

Examples:

* `$f = (new Filter('label1', 'value1'))->add('label2', Filter::OP_EXISTS);` filters items which have label1 = value1
and have label2.
* `$f = (new Filter('label1', 'value1'))->add('label2', Filter::OP_IN, ['a', 'b', 'c']);` filters items which have
label1 = value1 and where label2 is one of 'a', 'b' or 'c'.

## Local testing
For local testing you can use the provided `docker-compose.yml` file, which will create a PHP container (with the redis
extension pre-installed), and a redis container (with Redis Time Series included).

## Contributors
Thanks [Mrkisha](https://github.com/Mrkisha) for the precious contributions.
