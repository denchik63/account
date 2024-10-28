<?php

namespace App\Server\Parser\Exception;

class EventParsingException extends \RuntimeException implements ExceptionInterface
{
    public function getErrors(): array
    {
        return [$this->message];
    }
}
