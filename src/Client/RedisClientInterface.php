<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Client;

interface RedisClientInterface
{
    function executeCommand(array $params);
}