<?php
/**
 *
 * @author srako
 * @date 2024/9/12 09:33
 * @page http://srako.github.io
 */

namespace Webman\DingTalk\Models;

class DWClientDownStream
{
    public string $specVersion;

    public string $type;

    public array $headers;

    public string $data;

    public function __construct(array $response)
    {
        foreach ($response as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
}