<?php
/**
 *
 * @author srako
 * @date 2023/4/19 15:56
 * @page http://srako.github.io
 */

namespace Webman\DingTalk;

use Playcat\Queue\Manager;
use Playcat\Queue\Protocols\ProducerData;

class DingMessage extends ProducerData
{
    protected $channel = 'ding-message';

    public function __construct(array $message)
    {
        $this->setQueueData($message);
    }

    public static function dispatch(array $message): ?string
    {
        return Manager::getInstance()->push(new self($message));
    }
}