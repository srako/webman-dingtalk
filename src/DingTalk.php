<?php

namespace Webman\DingTalk;

use support\Container;

/**
 * Class Factory
 * @method static request(string $url, string $method, array $options = [], bool $withToken = true) 发送任意请求至钉钉
 * @method static get(string $api, array $params = []) 发送get请求至钉钉
 * @method static post(string $api, array $params = []) 发送post请求至钉钉
 * @package Webman\DingTalk
 */
class DingTalk
{
    public static function __callStatic($name, $arguments)
    {
        return Container::get(DingTalkManager::class)->{$name}(...$arguments);
    }
}
