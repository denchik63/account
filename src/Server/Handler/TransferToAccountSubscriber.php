<?php

namespace App\Server\Handler;

use App\Server\Db\AccountOperationsInterface;
use App\Server\Db\Exception\ExceptionInterface as DbException;
use App\Server\Enum\EventNames;
use App\Server\Event\NamedEvent;
use App\Server\EventData\Account\TransferToData;
use App\Server\Handler\Traits\AccountSubscriberTrait;
use App\Server\Parser\Exception\ExceptionInterface as ParsingException;
use App\Server\Parser\ParserInterface;
use App\Server\Response\ResponseCreatorInterface;
use App\Server\Response\ResponseDataFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TransferToAccountSubscriber implements EventSubscriberInterface
{
    use AccountSubscriberTrait;

    private const EventNames SUPPORTED_EVENT = EventNames::AccountTransferTo;

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

        /** @var ?TransferToData $transferToData */
        $transferToData = $this->parseData($namedEvent, TransferToData::class);
        if (null === $transferToData) {
            return;
        }

        try {
            $this->accountOperations->transferTo($transferToData->accountIdFrom, $transferToData->accountIdTo, $transferToData->sum);
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
