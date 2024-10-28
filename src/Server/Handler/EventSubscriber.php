<?php

namespace App\Server\Handler;

use App\Server\Event\MessageEvent;
use App\Server\Event\NamedEvent;
use App\Server\EventData\Base\Event;
use App\Server\Parser\Exception\ExceptionInterface;
use App\Server\Parser\ParserInterface;
use App\Server\Response\ResponseCreatorInterface;
use App\Server\Response\ResponseDataFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ParserInterface $parser,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly ResponseCreatorInterface $responseCreator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::NAME => 'handle',
        ];
    }

    public function handle(MessageEvent $messageEvent): void
    {
        try {
            /** @var Event $eventData */
            $eventData = $this->parser->parse($messageEvent->request, Event::class);
        } catch (ExceptionInterface $exception) {
            $response = $this->responseCreator->createResponse(ResponseDataFactory::createErrorResponseData('', '', $exception->getErrors()));
            $messageEvent->setResponse($response);

            return;
        }

        $namedEvent = new NamedEvent($eventData->name, $eventData->uid, $messageEvent->request);

        try {
            $this->eventDispatcher->dispatch($namedEvent, NamedEvent::NAME);
        } catch (\Throwable $exception) {
            $error = sprintf(
                'Error occurred while processing the event data, event name "%s", uid "%s", message "%s"',
                $eventData->name,
                $eventData->uid,
                $exception->getMessage()
            );
            $response = $this->responseCreator->createResponse(ResponseDataFactory::createErrorResponseData($eventData->name, $eventData->uid, [$error]));
            $messageEvent->setResponse($response);

            return;
        }

        $response = $namedEvent->getResponse();
        if (null === $response) {
            $error = sprintf('No response for event, name "%s", uid "%s"', $eventData->name, $eventData->uid);
            $response = $this->responseCreator->createResponse(ResponseDataFactory::createErrorResponseData($eventData->name, $eventData->uid, [$error]));
        }

        $messageEvent->setResponse($response);
    }
}
