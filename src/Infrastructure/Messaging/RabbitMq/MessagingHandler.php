<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Infrastructure\Messaging\RabbitMq\Model\Message;
use App\Server\Messaging\Exception\RuntimeException;
use App\Server\Messaging\MessagingInterface;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class MessagingHandler implements MessagingInterface
{
    public function __construct(
        private readonly AMQPStreamConnection $connection,
        private readonly string $responseQueueNamePostfix
    ) {
    }

    public function __destruct()
    {
        $this->connection->close();
    }

    public function handleRequests(string $queueName, callable $callback): void
    {
        $channel = $this->connection->channel();
        $channel->queue_declare($queueName, false, true, false, false);
        $internalCallback = function (AMQPMessage $message) use ($callback): void {
            $callback(new Message($message));
        };
        $channel->basic_consume($queueName, '', false, true, false, false, $internalCallback);

        while ($this->connection->isConnected()) {
            try {
                $channel->wait();
            } catch (\Throwable $exception) {
                throw new RuntimeException($exception->getMessage());
            }
        }

        $channel->close();
    }

    public function sendResponse(string $response, string $queueName): void
    {
        $responseQueueName = $queueName . $this->responseQueueNamePostfix;
        $channel = $this->connection->channel();
        $channel->queue_declare($responseQueueName, false, true, false, false);
        $message = new AMQPMessage(
            $response,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $channel->basic_publish($message, '', $responseQueueName);
        $channel->close();
    }
}
