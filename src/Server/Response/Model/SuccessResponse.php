<?php

namespace App\Server\Response\Model;

class SuccessResponse extends BaseResponse
{
    /** @param array<array-key, mixed> $data */
    public function __construct(
        public readonly string $eventName,
        public readonly string $eventUid,
        public readonly array $data = [],
    ) {
        parent::__construct(true);
    }
}
