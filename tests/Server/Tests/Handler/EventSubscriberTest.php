<?php

declare(strict_types=1);

namespace App\Tests\Server\Tests\Handler;

use App\Server\Event\MessageEvent;
use App\Server\Event\NamedEvent;
use App\Server\Handler\EventSubscriber;
use App\Server\Parser\ParserInterface;
use App\Server\Response\ResponseCreatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventSubscriberTest extends WebTestCase
{
    public function testHandleResponseWhenParsingError(): void
    {
        $testData = [
            [
                'messageEvent' => new MessageEvent('invalid_json'),
                'expectedResponse' => '{"success":false,"event_name":"","event_uid":"","errors":["Could not decode JSON, syntax error - malformed JSON."]}',
            ],
            [
                'messageEvent' => new MessageEvent('{"name": "", "uid": "123"}'),
                'expectedResponse' => '{"success":false,"event_name":"","event_uid":"","errors":["name: This value should not be blank."]}',
            ],
        ];

        foreach ($testData as $item) {
            $eventSubscriber = static::getContainer()->get(EventSubscriber::class);
            $messageEvent = $item['messageEvent'];
            $eventSubscriber->handle($messageEvent);
            $this->assertEquals($item['expectedResponse'], $messageEvent->getResponse());
        }
    }

    public function testHandleNamedEventDispatchedAndNoResponseForEvent(): void
    {
        $namedEvent = new NamedEvent('name', '123', '{"name": "name", "uid": "123"}');
        $container = static::getContainer();
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($namedEvent, NamedEvent::NAME);

        $eventSubscriber = new EventSubscriber(
            $container->get(ParserInterface::class),
            $eventDispatcherMock,
            $container->get(ResponseCreatorInterface::class)
        );

        $messageEvent = new MessageEvent('{"name": "name", "uid": "123"}');
        $eventSubscriber->handle($messageEvent);

        $this->assertEquals('{"success":false,"event_name":"name","event_uid":"123","errors":["No response for event, name \"name\", uid \"123\""]}', $messageEvent->getResponse());
    }

    public function testHandleNamedEventDispatchedWithException(): void
    {
        $namedEvent = new NamedEvent('name', '123', '{"name": "name", "uid": "123"}');
        $container = static::getContainer();
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($namedEvent, NamedEvent::NAME)
            ->willThrowException(new \RuntimeException('Some exception'));

        $eventSubscriber = new EventSubscriber(
            $container->get(ParserInterface::class),
            $eventDispatcherMock,
            $container->get(ResponseCreatorInterface::class)
        );

        $messageEvent = new MessageEvent('{"name": "name", "uid": "123"}');
        $eventSubscriber->handle($messageEvent);

        $this->assertEquals('{"success":false,"event_name":"name","event_uid":"123","errors":["Error occurred while processing the event data, event name \"name\", uid \"123\", message \"Some exception\""]}', $messageEvent->getResponse());
    }

    public function testHandleNamedEventDispatchedSuccessfully(): void
    {
        $namedEvent = new NamedEvent('name', '123', '{"name": "name", "uid": "123"}');
        $messageEvent = new MessageEvent('{"name": "name", "uid": "123"}');

        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $eventDispatcherMock
            ->expects($this->once())
            ->method('dispatch')
            ->with($namedEvent, NamedEvent::NAME)
            ->willReturnCallback(function () use ($namedEvent): object {
                $namedEvent->setResponse('{"success":true,"event_name":"name","event_uid":"123", "data": []}');

                return $namedEvent;
            })
        ;

        $container = static::getContainer();
        $eventSubscriber = new EventSubscriber(
            $container->get(ParserInterface::class),
            $eventDispatcherMock,
            $container->get(ResponseCreatorInterface::class)
        );

        $eventSubscriber->handle($messageEvent);

        $this->assertEquals('{"success":true,"event_name":"name","event_uid":"123", "data": []}', $namedEvent->getResponse());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(['message.event' => 'handle'], EventSubscriber::getSubscribedEvents());
    }
}
