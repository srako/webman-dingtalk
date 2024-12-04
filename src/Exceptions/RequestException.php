<?php
/**
 *
 */

namespace Webman\DingTalk\Exceptions;


use stdClass;

class RequestException extends \Exception
{
    public int $status = 500;
    public int|string $errorCode = 0;
    public string $errorMessage;

    private string $response;


    public function __construct(string|stdClass $response)
    {
        $response instanceof stdClass ? $this->buildByObject($response) : $this->buildByString($response);
        parent::__construct("钉钉接口报错: $this->errorMessage", 500);
    }


    private function buildByObject(stdClass $object): void
    {
        $this->errorCode = $object->errcode ?? $object->code;
        $this->errorMessage = $object->errmsg ?? $object->message;
        $this->response = json_encode($object, JSON_UNESCAPED_UNICODE);
    }


    private function buildByString(string $string): void
    {
        try {
            $this->buildByObject(json_decode($string));
        } catch (\Throwable) {
            $this->response = $string;
            $this->errorMessage = $string;
        }
    }

    public function getResponseBody(): string
    {
        return (string)$this->response;
    }
}
