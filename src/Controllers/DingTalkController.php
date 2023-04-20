<?php

namespace Webman\DingTalk\Controllers;

use support\Request;
use Webman\DingTalk\DingMessage;
use Webman\DingTalk\Services\CryptoService;

class DingTalkController
{

    public function callback(Request $request)
    {
        try {
            $text = CryptoService::decryptMsg(
                $request->signature,
                $request->timestamp,
                $request->nonce,
                $request->encrypt
            );

            DingMessage::dispatch(json_decode($text, true));

            // 为钉钉服务器返回成功状态
            return CryptoService::encryptMsg('success', $request->timestamp, $request->nonce);
        } catch (\Exception $e) {
            log('钉钉回调消息处理失败：' . $e->getMessage());
        }
        return CryptoService::encryptMsg('fail', $request->timestamp, $request->nonce);

    }
}
