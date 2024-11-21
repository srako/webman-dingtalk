<?php
/**
 * 钉钉事件订阅消息
 * @author srako
 * @date 2023/4/19 15:56
 * @page http://srako.github.io
 */

namespace Webman\DingTalk\Messages;


use Webman\RedisQueue\Redis;

class DingMessage
{
    protected static string $channel = 'ding-message';

    public static function dispatch(array $message): bool
    {
        return Redis::send(static::$channel, $message);
    }
}