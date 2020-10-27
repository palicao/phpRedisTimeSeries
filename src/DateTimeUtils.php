<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

use DateTimeImmutable;
use DateTimeInterface;
use Palicao\PhpRedisTimeSeries\Exception\TimestampParsingException;

class DateTimeUtils
{
    public static function dateTimeFromTimestampWithMs(int $timestamp) : DateTimeInterface
    {
        $dateTime = DateTimeImmutable::createFromFormat('U.u', sprintf('%.03f', $timestamp / 1000));
        if ($dateTime === false) {
            throw new TimestampParsingException(sprintf("Unable to parse timestamp: %d", $timestamp));
        }
        return $dateTime;
    }

    public static function timestampWithMsFromDateTime(DateTimeInterface $dateTime) : int
    {
        return (int)round((int)$dateTime->format('Uu') / 1000);
    }
}
