<?php

namespace Webman\DingTalk;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use stdClass;
use support\Cache;
use support\Log;
use Webman\DingTalk\Exceptions\RequestException;

class DingTalkManager
{
    protected array $config;

    // 旧版SDK接口地址
    protected string $baseUrl = 'https://oapi.dingtalk.com';

    // 新版SDK接口地址
    protected string $newBaseUrl = 'https://api.dingtalk.com';

    public function __construct(array $config = [])
    {
        $this->config = config('plugin.srako.dingtalk.app') + $config;
    }


    /**
     * 获取应用token
     * @return string|null
     * @throws RequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @link https://open.dingtalk.com/document/orgapp/api-gettoken
     */
    public function token(): ?string
    {
        $token = Cache::get('dingtalk.' . $this->config['corpid']);
        if ($token) {
            return $token;
        }
        $res = $this->request("/v1.0/oauth2/{$this->config['corpid']}/token", 'post', [
            'json' => [
                'client_id' => $this->config['appkey'],
                'client_secret' => $this->config['appsecret'],
                'grant_type' => 'client_credentials'
            ]
        ], false);
        if (is_null($res)) {
            return null;
        }
        Cache::set('dingtalk.' . $this->config['corpid'], $res->access_token, $res->expires_in - 60);
        return $res->access_token;
    }

    /**
     * 获取企业内部应用accessToken
     * @return string|null
     * @throws RequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @link https://open.dingtalk.com/document/orgapp/obtain-the-access_token-of-an-internal-app
     */
    public function accessToken(): ?string
    {
        $token = Cache::get('dingtalk.internal.' . $this->config['corpid']);
        if ($token) {
            return $token;
        }
        $res = $this->request("/v1.0/oauth2/accessToken", 'post', [
            'json' => [
                'appKey' => $this->config['appkey'],
                'appSecret' => $this->config['appsecret']
            ]
        ], false);
        if (is_null($res)) {
            return null;
        }
        Cache::set('dingtalk.internal.' . $this->config['corpid'], $res->accessToken, $res->expireIn - 60);
        return $res->accessToken;
    }

    /**
     * 发送钉钉请求
     * @param string $url
     * @param string $method
     * @param array $params
     * @param bool $withToken
     * @return mixed|string
     * @throws RequestException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(string $url, string $method, array $params = [], bool $withToken = true)
    {
        // 是否旧版SDK
        $isOld = Str::startsWith($url, ['/topapi', 'topapi']);
        if ($withToken) {
            $isOld && $params['query']['access_token'] = $this->token();
            !$isOld && $params['headers']['x-acs-dingtalk-access-token'] = $this->token();
        }
        // 新旧版接口地址不同
        $baseUrl = $isOld ? $this->baseUrl : $this->newBaseUrl;
        $client = new Client(['base_uri' => $baseUrl]);
        $response = $client->request($method, $url, $params);
        if ($response->getStatusCode() === 200) {
            $string = $response->getBody()->getContents();
            Log::info("[webman-dingtalk][$method][$baseUrl][$url]" . $string);
            return json_decode($string) ?: $string;
        }
        Log::error("[webman-dingtalk][$method][$baseUrl][$url]" . $response->getBody());
        throw new RequestException($response->getBody());
    }

    /**
     * 发送get请求至钉钉
     * @param string $api
     * @param array $params
     * @return stdClass
     * @throws RequestException
     */
    public function get(string $api, array $params = []): stdClass
    {
        $res = $this->request($api, 'get', ['query' => $params]);
        if (is_string($res) || (isset($res->errcode) && $res->errcode !== 0)) {
            throw new RequestException($res);
        }
        return $res;
    }

    /**
     * 发送post请求至钉钉
     * @param string $api
     * @param array $params
     * @return stdClass
     * @throws RequestException
     */
    public function post(string $api, array $params = []): stdClass
    {
        $res = $this->request($api, 'post', ['json' => $params]);
        if (is_string($res) || (isset($res->errcode) && $res->errcode !== 0)) {
            throw new RequestException($res);
        }
        return $res;
    }


    /**
     * 设置为指定的企业id
     * @param string $corpId
     * @return $this
     */
    public function corp(string $corpId): static
    {
        if ($corpId) {
            $this->config['corpid'] = $corpId;
        }
        return $this;
    }
}
