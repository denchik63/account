<?php

namespace App\Server\Event;

use Symfony\Contracts\EventDispatcher\Event;

class NamedEvent extends Event
{
    public const string NAME = 'named.event';

    private ?string $response;

    public function __construct(
        public readonly string $eventName,
        public readonly string $eventUid,
        public readonly string $request,
    ) {
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
