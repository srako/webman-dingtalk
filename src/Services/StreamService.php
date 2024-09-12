<?php
/**
 * 钉钉stream事件监听
 * @author srako
 * @date 2024/9/12 09:04
 * @page http://srako.github.io
 */

namespace Webman\DingTalk\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use support\Log;
use Webman\DingTalk\Exceptions\RequestException;
use Webman\DingTalk\Models\DWClientDownStream;
use Workerman\Connection\AsyncTcpConnection;

class StreamService
{
    private array $config = [
        'ua' => '',
        'subscriptions' => [
            [
                "type" => 'EVENT',
                "topic" => '*',
            ]
        ]
    ];
    /**
     * websocket网关
     * @var string
     */
    private string $gatewayUrl = 'https://api.dingtalk.com/v1.0/gateway/connections/open';

    /**
     * websocket 地址
     * @var string
     */
    private string $dwUrl;
    private AsyncTcpConnection $socket;

    private array $callbackEvent;

    private $onEventReceived;

    public function __construct()
    {
        $this->config = array_merge($this->config, config('plugin.srako.dingtalk.app'));
    }

    public function registerAllEventListener(callable $onEventReceived): static
    {
        $this->onEventReceived = $onEventReceived;
        return $this;
    }

    public function registerCallbackListener(string $eventId, callable $callback): void
    {
        $has = array_filter($this->config['subscriptions'], function ($item) use ($eventId) {
            return $item['topic'] === $eventId && $item['type'] === 'CALLBACK';
        });
        if (!$has) {
            $this->config['subscriptions'][] = [
                "type" => 'CALLBACK',
                "topic" => $eventId,
            ];
            $this->callbackEvent[$eventId] = $callback;
        }
    }

    public function run()
    {
        $this->getEndpoint()->connect();
    }

    private function getEndpoint(): static
    {
        $client = new Client(['base_uri' => $this->gatewayUrl]);
        $response = $client->request('POST', '', [
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => $this->config['ua']
            ],
            "json" => [
                'clientId' => $this->config['appkey'],
                'clientSecret' => $this->config['appsecret'],
                'ua' => $this->config['ua'],
                'subscriptions' => $this->config['subscriptions']
            ]
        ]);
        if ($response->getStatusCode() === 200) {
            $string = $response->getBody()->getContents();
            Log::info("[webman-dingtalk][$this->gatewayUrl]" . $string);
            if ($data = json_decode($string, true)) {
                $url = Str::replaceFirst('wss://', 'ws://', $data['endpoint']);
                $this->dwUrl = "$url?ticket={$data['ticket']}";
                return $this;
            }
        }
        Log::error("[webman-dingtalk][$this->gatewayUrl]" . $response->getBody());
        throw new RequestException($response->getBody());
    }

    private function connect()
    {
        // 以websocket协议连接远程websocket服务器
        $this->socket = new AsyncTcpConnection($this->dwUrl);

        // 设置以ssl加密方式访问，使之成为wss
        $this->socket->transport = 'ssl';

        // 每隔55秒向服务端发送一个opcode为0x9的websocket心跳
        $this->socket->websocketPingInterval = 30;
        // 当TCP完成三次握手后
        $this->socket->onConnect = function ($connection) {
            Log::info("[webman-dingtalk][$this->dwUrl] websocket connected");
        };
        // 当websocket完成握手后
        $this->socket->onWebSocketConnect = function (AsyncTcpConnection $con, $response) {
            Log::info("[webman-dingtalk][$this->dwUrl] websocket connected done");
        };
        // 远程websocket服务器发来消息时
        $this->socket->onMessage = function ($connection, $message) {
            Log::info("[webman-dingtalk][$this->dwUrl] websocket received message");
            $msg = json_decode($message, true);
            Log::info("[webman-dingtalk][$this->dwUrl] websocket message content", $msg);
            $message = new DWClientDownStream($msg);
            switch ($message->type) {
                case 'SYSTEM':
                    $this->onSystem($message);
                    break;
                case 'EVENT':
                    $this->onEvent($message);
                    break;
                case 'CALLBACK':
                    $this->onCallback($message);
                    break;
            }
        };
        // 连接上发生错误时，一般是连接远程websocket服务器失败错误
        $this->socket->onError = function ($connection, $code, $msg) {
            Log::error("[webman-dingtalk][$this->dwUrl] websocket error: $msg");
        };

        // 当连接远程websocket服务器的连接断开时
        $this->socket->onClose = function ($connection) {
            Log::error("[webman-dingtalk][$this->dwUrl] websocket connection closed and try to reconnect");

            $this->getEndpoint();
            // 如果连接断开，1秒后重连
            $connection->reConnect(1);
        };
        // 设置好以上各种回调后，执行连接操作
        $this->socket->connect();
    }

    private function onSystem(DWClientDownStream $downstream)
    {
        switch ($downstream->headers['topic']) {
            case 'KEEPALIVE':
            case 'disconnect':
            case 'REGISTERED':
            case 'CONNECTED':
                break;
            case 'ping':
                $this->socket->send(json_encode([
                    'code' => 200,
                    'headers' => $downstream->headers,
                    'message' => 'OK',
                    'data' => $downstream->data
                ]));
                break;
        }
    }

    private function onEvent(DWClientDownStream $message)
    {
        // 触发事件
        $call = $this->onEventReceived;
        $call($message);
        $this->send($message->headers['messageId'], json_encode(['status' => "SUCCESS"]));
    }

    private function onCallback(DWClientDownStream $message)
    {
        if (!isset($this->callbackEvent[$message->headers['topic']])) {
            $this->callbackEvent[$message->headers['topic']]($message);
            // 默认处理成功
            $this->send($message->headers['messageId'], json_encode(['status' => "SUCCESS"]));
        }
    }


    private function send(string $messageId, string $message)
    {
        $this->socket->send(json_encode([
            'code' => 200,
            'headers' => [
                'contentType' => 'application/json',
                'messageId' => $messageId
            ],
            'message' => 'OK',
            'data' => $message
        ]));
    }
}