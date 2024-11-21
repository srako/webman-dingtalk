<?php

namespace Webman\DingTalk;

use support\Container;

/**
 * Class Factory
 * @method static DingTalkManager corp(string $corpId) 设置企业id并返回DingTalkManager实例
 * @method static DingTalkManager request(string $url, string $method, array $options = [], bool $withToken = true) 发送任意请求至钉钉
 * @method static DingTalkManager get(string $api, array $params = []) 发送get请求至钉钉
 * @method static DingTalkManager post(string $api, array $params = []) 发送post请求至钉钉
 * @package Webman\DingTalk
 */
class DingTalk
{
    public static function __callStatic($name, $arguments)
    {
        return Container::get(DingTalkManager::class)->{$name}(...$arguments);
    }


}
