<?php
/**
 * 子进程配置
 * @author srako
 * @date 2024/9/11 18:37
 * @page http://srako.github.io
 */

namespace Webman\DingTalk\Process;

use Webman\DingTalk\Messages\DingMessage;
use Webman\DingTalk\Models\DWClientDownStream;
use Webman\DingTalk\Services\StreamService;

class Stream
{

    public function onWorkerStart()
    {
        $stream = new StreamService();
        $stream->registerAllEventListener(function (DWClientDownStream $message) {
            DingMessage::dispatch($message->toArray());
        })->run();
    }
}