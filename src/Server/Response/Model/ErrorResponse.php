<?php

namespace App\Server\Response\Model;

class ErrorResponse extends BaseResponse
{
    /** @param array<string> $errors */
    public function __construct(
        public readonly string $eventName,
        public readonly string $eventUid,
        public readonly array $errors,
    ) {
        parent::__construct(false);
    }
}
