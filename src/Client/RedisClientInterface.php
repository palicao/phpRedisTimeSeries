<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Client;

use RedisException;

interface RedisClientInterface
{
    /**
     * @param string[] $params
     * @return mixed
     * @throws RedisException
     */
    public function executeCommand(array $params);
}
