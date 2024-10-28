<?php

namespace App\Server\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MessageEvent extends Event
{
    public const string NAME = 'message.event';

    private ?string $response;

    public function __construct(public readonly string $request)
    {
        $this->response = null;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }
}
