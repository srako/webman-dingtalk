<?php

namespace Webman\DingTalk;

use GuzzleHttp\Client;
use stdClass;
use support\Cache;
use support\Log;
use Webman\DingTalk\Exceptions\RequestException;

class DingTalkManager
{
    protected array $config;

    protected string $baseUrl = 'https://oapi.dingtalk.com';

    public function __construct(array $config = [])
    {
        $this->config = $config ?: config('plugin.srako.dingtalk.app');
    }

    public function getAccessToken(): ?string
    {
        $key = 'dingtalk.' . $this->config['corpid'];
        $token = Cache::get($key);
        if ($token) {
            return $token;
        }
        $res = $this->request('/gettoken', 'get', [
            'query' => [
                'appkey' => $this->config['appkey'],
                'appsecret' => $this->config['appsecret'],
            ]
        ], false);
        if (is_null($res) || $res->errcode !== 0) {
            return null;
        }
        Cache::set($key, $res->access_token, $res->expires_in - 60);
        return $res->access_token;
    }

    private function request(string $url, string $method, array $params = [], bool $withToken = true)
    {
        if ($withToken) {
            $params['query']['access_token'] = $this->getAccessToken();
        }
        $client = new Client(['base_uri' => $this->baseUrl]);
        $response = $client->request($method, $url, $params);
        if ($response->getStatusCode() === 200) {
            $string = $response->getBody()->getContents();
            Log::info("[webman-dingtalk][$method][$this->baseUrl][$url]" . $string);
            return json_decode($string) ?: $string;
        }
        Log::error("[webman-dingtalk][$method][$this->baseUrl][$url]" . $response->getBody());
        throw new RequestException($response->getBody());
    }

    /**
     * 发送get请求至钉钉
     * @param string $api
     * @param array $params
     * @return stdClass
     */
    public function get(string $api, array $params = []): stdClass
    {
        $res = $this->request($api, 'get', ['query' => $params]);
        if (is_string($res) || $res->errcode !== 0) {
            throw new RequestException($res);
        }
        return $res;
    }

    /**
     * 发送post请求至钉钉
     * @param string $api
     * @param array $params
     * @return stdClass
     */
    public function post(string $api, array $params = []): stdClass
    {
        $res = $this->request($api, 'post', [
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'form_params' => $params
        ]);
        if (is_string($res) || $res->errcode !== 0) {
            throw new RequestException($res);
        }
        return $res;
    }
}
