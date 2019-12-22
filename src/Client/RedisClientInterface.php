<?php
declare(strict_types=1);

namespace Palicao\PhpRedisTimeSeries\Client;

interface RedisClientInterface
{
    /**
     * @param array $params
     * @return mixed
     */
    public function executeCommand(array $params);
}
