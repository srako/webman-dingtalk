<?php
/**
 *
 * @author srako
 * @date 2023/4/19 15:56
 * @page http://srako.github.io
 */

namespace Webman\DingTalk;


use Webman\RedisQueue\Redis;

class DingMessage
{
    protected static string $channel = 'ding-message';

    public static function dispatch(array $message): bool
    {
        return Redis::send(self::$channel, $message);
    }
}