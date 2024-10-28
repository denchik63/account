<?php

namespace App\Server\Messaging;

use App\Server\Messaging\Exception\ExceptionInterface;

interface MessagingInterface
{
    /** @throws ExceptionInterface */
    public function handleRequests(string $queueName, callable $callback): void;

    /** @throws ExceptionInterface */
    public function sendResponse(string $response, string $queueName): void;
}
