<?php

namespace App\Infrastructure\Messaging\RabbitMq;

use App\Infrastructure\Messaging\RabbitMq\Model\Message;
use App\Server\Messaging\Exception\ExceptionInterface;
use App\Server\Messaging\Exception\RuntimeException;
use App\Server\Messaging\Exception\StopHandlingException;
use App\Server\Messaging\Exception\TimeoutExceededException;
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
        $this->handleChannel($queueName, $callback);
    }

    public function sendResponse(string $response, string $queueName): void
    {
        $responseQueueName = $queueName . $this->responseQueueNamePostfix;
        $this->sendRequestDataToChannel($responseQueueName, $response);
    }

    public function sendRequest(string $request, string $queueName): void
    {
        $this->sendRequestDataToChannel($queueName, $request);
    }

    /** @throws ExceptionInterface */
    public function handleResponse(string $queueName, callable $callback, int $timeout): void
    {
        $this->handleChannel($queueName . $this->responseQueueNamePostfix, $callback, $timeout);
    }

    private function sendRequestDataToChannel(string $queueName, string $request): void
    {
        $channel = $this->connection->channel();
        $channel->queue_declare($queueName, false, true, false, false);
        $message = new AMQPMessage(
            $request,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $channel->basic_publish($message, '', $queueName);
        $channel->close();
    }

    private function handleChannel(string $queueName, callable $callback, ?int $timeout = null): void
    {
        $channel = $this->connection->channel();
        $channel->queue_declare($queueName, false, true, false, false);
        $internalCallback = function (AMQPMessage $message) use ($callback): void {
            $callback(new Message($message));
        };
        $channel->basic_consume($queueName, '', false, true, false, false, $internalCallback);

        $startTime = microtime(true);
        while ($this->connection->isConnected()) {
            try {
                $channel->wait();
            } catch (StopHandlingException) {
                break;
            } catch (\Throwable $exception) {
                throw new RuntimeException($exception->getMessage());
            }

            if (Null !== $timeout && (microtime(true) - $startTime) > $timeout) {
                throw new TimeoutExceededException();
            }
        }

        $channel->close();
    }
}
