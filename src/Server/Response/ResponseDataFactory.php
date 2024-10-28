<?php

namespace App\Server\Response;

use App\Server\Response\Model\ErrorResponse;
use App\Server\Response\Model\SuccessResponse;

class ResponseDataFactory
{
    /** @param array<string> $errors */
    public static function createErrorResponseData(string $eventName, string $eventUid, array $errors): ErrorResponse
    {
        return new ErrorResponse($eventName, $eventUid, $errors);
    }

    /** @param array<array-key, mixed> $data */
    public static function createSuccessResponseData(string $eventName, string $eventUid, array $data = []): SuccessResponse
    {
        return new SuccessResponse($eventName, $eventUid, $data);
    }
}
