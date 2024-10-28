<?php

namespace App\Server\Handler\Traits;

use App\Server\Event\NamedEvent;
use App\Server\EventData\BaseData;
use App\Server\Parser\Exception\ExceptionInterface as ParsingException;
use App\Server\Response\ResponseDataFactory;

trait AccountSubscriberTrait
{
    private function parseData(NamedEvent $namedEvent, string $dataClassName): ?BaseData
    {
        try {
            $data = $this->parser->parse($namedEvent->request, $dataClassName);
        } catch (ParsingException $exception) {
            $response = $this->responseCreator->createResponse(
                ResponseDataFactory::createErrorResponseData($namedEvent->eventName, $namedEvent->eventUid, $exception->getErrors())
            );
            $namedEvent->setResponse($response);

            return null;
        }

        return $data;
    }
}