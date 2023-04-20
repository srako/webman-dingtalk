# Webman Dingtalk

封装钉钉接口，处理钉钉事件订阅，触发事件

## 安装

```
composer require srako/wenman-dingtalk
```

## 配置

1. 添加 .env 环境变量

```
DING_CORP_ID=dingxxxxxxx
DING_AGENT_ID=xxxxxxxx
DING_CLIENT_ID=xxxxxxxx
DING_CLIENT_SECRET=xxxxxxxx
DING_AES_KEY=xxxxxxxx
DING_TOKEN=xxxxxxxx
```


## 添加的命令

1. 刷新部门和用户（触发变更事件）

```bash
php webman dingtalk:RefreshDepartmentsAndUsers
```

## 钉钉接口调用示例

### 发送工作通知消息

```
请求方式：POST（HTTPS）
请求地址：https://oapi.dingtalk.com/topapi/message/corpconversation/asyncsend_v2?access_token=ACCESS_TOKEN
```

```php
$params = [
    'agent_id' => env('DINGTALK_AGENTID'),
    'userid_list' => '0841582759859766',
    'msg' => [
        'msgtype' => 'text',
        'text' => [
            'content' => '当前时间：'.date('Y-m-d H:i:s'),
        ],
    ],
];

$ret = DingTalk::post('/topapi/message/corpconversation/asyncsend_v2', $params);
```
