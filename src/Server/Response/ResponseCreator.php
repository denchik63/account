<?php

namespace App\Server\Response;

use App\Server\Response\Model\BaseResponse;
use JMS\Serializer\SerializerInterface;

class ResponseCreator implements ResponseCreatorInterface
{
    public function __construct(private readonly SerializerInterface $serializer, private readonly string $dataFormat)
    {
    }

    public function createResponse(BaseResponse $response): string
    {
        return $this->serializer->serialize($response, $this->dataFormat);
    }
}
