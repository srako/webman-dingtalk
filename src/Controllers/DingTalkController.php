<?php

namespace Webman\DingTalk\Controllers;

use support\Log;
use support\Request;
use Webman\DingTalk\Messages\DingMessage;
use Webman\DingTalk\Services\CryptoService;

class DingTalkController
{

    public function callback(Request $request)
    {
        try {
            $text = CryptoService::decryptMsg(
                $request->input('signature'),
                $request->input('timestamp'),
                $request->input('nonce'),
                $request->input('encrypt')
            );

            DingMessage::dispatch(json_decode($text, true));

            // 为钉钉服务器返回成功状态
            return CryptoService::encryptMsg('success', $request->input('timestamp'), $request->input('nonce'));
        } catch (\Exception $e) {
            Log::error('钉钉回调消息处理失败:'.$e->getMessage());
        }
        return CryptoService::encryptMsg('fail', $request->input('timestamp'), $request->input('nonce'));

    }
}
