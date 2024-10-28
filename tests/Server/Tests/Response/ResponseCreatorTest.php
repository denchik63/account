<?php

declare(strict_types=1);

namespace App\Tests\Server\Tests\Response;

use App\Server\Response\Model\SuccessResponse;
use App\Server\Response\ResponseCreator;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;

class ResponseCreatorTest extends TestCase
{
    public function testCreateResponse(): void
    {
        $successResponseObject = new SuccessResponse('eventName', 'eventUid', ['some-data']);

        $serializerMock = $this->createMock(SerializerInterface::class);
        $serializerMock
            ->expects($this->once())
            ->method('serialize')
            ->with($successResponseObject, 'json')
        ;

        $responseCreator = new ResponseCreator($serializerMock, 'json');
        $responseCreator->createResponse($successResponseObject);
    }
}
