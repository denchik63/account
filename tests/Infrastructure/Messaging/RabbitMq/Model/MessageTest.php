<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Messaging\RabbitMq\Model;

use App\Infrastructure\Messaging\RabbitMq\Model\Message;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testGetRequest(): void
    {
        $aMQPMessage = new AMQPMessage('{"name": "value"}');
        $massage = new Message($aMQPMessage);
        $this->assertEquals('{"name": "value"}', $massage->getData());
    }
}