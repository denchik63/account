<?php

namespace App\Server\Handler;

use App\Server\Db\AccountOperationsInterface;
use App\Server\Db\Exception\ExceptionInterface as DbException;
use App\Server\Enum\EventNames;
use App\Server\Event\NamedEvent;
use App\Server\EventData\Account\WriteOffData;
use App\Server\Parser\Exception\ExceptionInterface as ParsingException;
use App\Server\Parser\ParserInterface;
use App\Server\Response\ResponseCreatorInterface;
use App\Server\Response\ResponseDataFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WriteOffAccountSubscriber implements EventSubscriberInterface
{
    private const EventNames SUPPORTED_EVENT = EventNames::AccountWriteOff;

    public function __construct(
        private readonly ParserInterface $parser,
        private readonly AccountOperationsInterface $accountOperations,
        private readonly ResponseCreatorInterface $responseCreator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NamedEvent::NAME => 'handle',
        ];
    }

    public function handle(NamedEvent $namedEvent): void
    {
        if ($namedEvent->isPropagationStopped()) {
            return;
        }

        if (self::SUPPORTED_EVENT->value !== $namedEvent->eventName) {
            return;
        }

        $namedEvent->stopPropagation();

        try {
            /** @var WriteOffData $writeOffData */
            $writeOffData = $this->parser->parse($namedEvent->request, WriteOffData::class);
        } catch (ParsingException $exception) {
            $response = $this->responseCreator->createResponse(
                ResponseDataFactory::createErrorResponseData($namedEvent->eventName, $namedEvent->eventUid, $exception->getErrors())
            );
            $namedEvent->setResponse($response);

            return;
        }

        try {
            $this->accountOperations->writeOff($writeOffData->accountId, $writeOffData->sum);
        } catch (DbException $exception) {
            $response = $this->responseCreator->createResponse(
                ResponseDataFactory::createErrorResponseData($namedEvent->eventName, $namedEvent->eventUid, [$exception->getMessage()])
            );
            $namedEvent->setResponse($response);

            return;
        }

        $namedEvent->setResponse($this->responseCreator->createResponse(
            ResponseDataFactory::createSuccessResponseData($namedEvent->eventName, $namedEvent->eventUid))
        );
    }
}
