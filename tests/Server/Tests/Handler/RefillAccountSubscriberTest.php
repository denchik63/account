<?php

declare(strict_types=1);

namespace App\Tests\Server\Tests\Handler;

use App\Server\Db\AccountOperationsInterface;
use App\Server\Event\NamedEvent;
use App\Server\Handler\RefillAccountSubscriber;
use App\Server\Parser\ParserInterface;
use App\Server\Response\ResponseCreatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Server\Db\Exception\AccountNotFoundException as AccountNotFoundException;

class RefillAccountSubscriberTest extends WebTestCase
{
    public function testHandleReturnWhenPropagationStopped(): void
    {
        $namedEventMock = $this->createMock(NamedEvent::class);
        $namedEventMock->expects($this->once())->method('isPropagationStopped')->willReturn(true);
        $namedEventMock->expects($this->never())->method('stopPropagation');
        $namedEventMock->expects($this->never())->method('setResponse');
        static::getContainer()->get(RefillAccountSubscriber::class)->handle($namedEventMock);
    }

    public function testHandleReturnWhenEventNotSupported(): void
    {
        $namedEventMock = $this->getMockBuilder(NamedEvent::class)
            ->setConstructorArgs(['name', '123', '{"name": "name", "uid": "123"}'])
            ->getMock();

        $namedEventMock->expects($this->once())->method('isPropagationStopped')->willReturn(false);
        $namedEventMock->expects($this->never())->method('stopPropagation');
        $namedEventMock->expects($this->never())->method('setResponse');
        static::getContainer()->get(RefillAccountSubscriber::class)->handle($namedEventMock);
    }

    public function testHandleResponseWhenParsingError(): void
    {
        $testData = [
            [
                'namedEvent' => new NamedEvent('account.refill', '123', 'invalid_json'),
                'expectedResponse' => '{"success":false,"event_name":"account.refill","event_uid":"123","errors":["Could not decode JSON, syntax error - malformed JSON."]}',
            ],
            [
                'namedEvent' => new NamedEvent('account.refill', '123', '{"name": "account.refill", "uid": "123"}'),
                'expectedResponse' => '{"success":false,"event_name":"account.refill","event_uid":"123","errors":["accountId: This value should not be blank.","sum: This value should not be blank."]}',
            ],
        ];

        foreach ($testData as $item) {
            $eventSubscriber = static::getContainer()->get(RefillAccountSubscriber::class);
            $namedEvent = $item['namedEvent'];
            $eventSubscriber->handle($namedEvent);
            $this->assertEquals($item['expectedResponse'], $namedEvent->getResponse());
        }
    }

    public function testHandleResponseWhenRefillingError(): void
    {
        $namedEvent = new NamedEvent('account.refill', '123', '{"name": "account.refill", "uid": "123", "account_id": "1", "sum": 123.0}');

        $accountOperationsInterfaceMock = $this->createMock(AccountOperationsInterface::class);
        $accountOperationsInterfaceMock
            ->expects($this->once())
            ->method('refill')
            ->with('1', 123.0)
            ->willThrowException(new AccountNotFoundException('Some error'))
        ;

        $container = static::getContainer();
        $refillAccountSubscriber = new RefillAccountSubscriber(
            $container->get(ParserInterface::class),
            $accountOperationsInterfaceMock,
            $container->get(ResponseCreatorInterface::class)
        );

        $refillAccountSubscriber->handle($namedEvent);

        $this->assertEquals('{"success":false,"event_name":"account.refill","event_uid":"123","errors":["Some error"]}', $namedEvent->getResponse());
    }

    public function testHandleResponseWhenRefillingSuccessfully(): void
    {
        $namedEvent = new NamedEvent('account.refill', '123', '{"name": "account.refill", "uid": "123", "account_id": "1", "sum": 123.0}');

        $accountOperationsInterfaceMock = $this->createMock(AccountOperationsInterface::class);
        $accountOperationsInterfaceMock
            ->expects($this->once())
            ->method('refill')
            ->with('1', 123.0)
        ;

        $container = static::getContainer();
        $refillAccountSubscriber = new RefillAccountSubscriber(
            $container->get(ParserInterface::class),
            $accountOperationsInterfaceMock,
            $container->get(ResponseCreatorInterface::class)
        );

        $refillAccountSubscriber->handle($namedEvent);

        $this->assertEquals('{"success":true,"event_name":"account.refill","event_uid":"123","data":[]}', $namedEvent->getResponse());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(['named.event' => 'handle'], RefillAccountSubscriber::getSubscribedEvents());
    }
}