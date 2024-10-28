<?php

namespace App\Infrastructure\Messaging\RabbitMq\Model;

use App\Server\Messaging\Model\BaseMessageInterface;
use PhpAmqpLib\Message\AMQPMessage;

class Message implements BaseMessageInterface
{
    public function __construct(private readonly AMQPMessage $message)
    {
    }

    public function getRequest(): string
    {
        return $this->message->getBody();
    }
}
