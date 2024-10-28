<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Messaging\RabbitMq;

use App\Infrastructure\Messaging\RabbitMq\MessagingHandler;
use App\Infrastructure\Messaging\RabbitMq\Model\Message;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;

class MessagingHandlerTest extends TestCase
{
    public function testHandleRequests(): void
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->once())->method('queue_declare')->with('test_queue_name', false, true, false, false);
        $callback = function (Message $message): void {};
        $internalCallback = function (AMQPMessage $message) use ($callback): void {
            $callback(new Message($message));
        };
        $channelMock->expects($this->once())->method('basic_consume')->with('test_queue_name', '', false, true, false, false, $internalCallback);
        $channelMock->expects($this->once())->method('wait');
        $channelMock->expects($this->once())->method('close');
        $aMQPStreamConnectionMock = $this->createMock(AMQPStreamConnection::class);
        $aMQPStreamConnectionMock->expects($this->once())->method('channel')->willReturn($channelMock);
        $aMQPStreamConnectionMock->expects($this->exactly(2))->method('isConnected')->willReturnOnConsecutiveCalls(true, false);

        $messagingHandler = new MessagingHandler($aMQPStreamConnectionMock, '.response');
        $messagingHandler->handleRequests('test_queue_name', $callback);
    }

    public function testHandleRequestsWhenException(): void
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->once())->method('queue_declare')->with('test_queue_name', false, true, false, false);
        $callback = function (Message $message): void {};
        $internalCallback = function (AMQPMessage $message) use ($callback): void {
            $callback(new Message($message));
        };
        $channelMock->expects($this->once())->method('basic_consume')->with('test_queue_name', '', false, true, false, false, $internalCallback);
        $channelMock->expects($this->once())->method('wait')->willThrowException(new \RuntimeException('Some exception'));
        $channelMock->expects($this->never())->method('close');
        $aMQPStreamConnectionMock = $this->createMock(AMQPStreamConnection::class);
        $aMQPStreamConnectionMock->expects($this->once())->method('channel')->willReturn($channelMock);
        $aMQPStreamConnectionMock->expects($this->once())->method('isConnected')->willReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Some exception');

        $messagingHandler = new MessagingHandler($aMQPStreamConnectionMock, '.response');
        $messagingHandler->handleRequests('test_queue_name', $callback);
    }

    public function testSendResponse(): void
    {
        $channelMock = $this->createMock(AMQPChannel::class);
        $channelMock->expects($this->once())->method('queue_declare')->with('test_queue_name.response', false, true, false, false);
        $message = new AMQPMessage(
            '{"name": "name": "uid": "123"}',
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $channelMock->expects($this->once())->method('basic_publish')->with($message, '', 'test_queue_name.response');
        $channelMock->expects($this->once())->method('close');
        $aMQPStreamConnectionMock = $this->createMock(AMQPStreamConnection::class);
        $aMQPStreamConnectionMock->expects($this->once())->method('channel')->willReturn($channelMock);

        $messagingHandler = new MessagingHandler($aMQPStreamConnectionMock, '.response');
        $messagingHandler->sendResponse('{"name": "name": "uid": "123"}', 'test_queue_name');
    }
}