<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries;

/** @psalm-immutable */
final class Label
{
    /** @var string */
    private $key;

    /** @var string */
    private $value;

    /**
     * @param string $key
     * @param string $value
     */
    public function __construct(string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
