<?php
/**
 *
 */

namespace Webman\DingTalk\Exceptions;


use stdClass;

class RequestException extends \Exception
{
    public int $status = 500;
    public int $errorCode = 0;
    public string $errorMessage;


    public function __construct(string|stdClass $response)
    {
        if ($response instanceof stdClass) {
            $this->errorCode = $response->errcode;
            $this->errorMessage = $response->errmsg;
        } else {
            $this->errorMessage = $response;
        }

        parent::__construct('钉钉接口未正确响应', 500);
    }
}
