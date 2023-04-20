<?php
/**
 * 钉钉事件订阅路由
 * @author srako
 * @date 2023/4/11 16:28
 * @page http://srako.github.io
 */
use Webman\Route;

Route::post('/api/dingtalk/callback', [\Webman\DingTalk\Controllers\DingTalkController::class, 'callback']);