<?php

declare(strict_types=1);

namespace App\Tests\Server\Tests\Handler;

use App\Server\Db\AccountOperationsInterface;
use App\Server\Event\NamedEvent;
use App\Server\Handler\TransferToAccountSubscriber;
use App\Server\Parser\ParserInterface;
use App\Server\Response\ResponseCreatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Server\Db\Exception\AccountNotFoundException as AccountNotFoundException;

class TransferToAccountSubscriberTest extends WebTestCase
{
    public function testHandleReturnWhenPropagationStopped(): void
    {
        $namedEventMock = $this->createMock(NamedEvent::class);
        $namedEventMock->expects($this->once())->method('isPropagationStopped')->willReturn(true);
        $namedEventMock->expects($this->never())->method('stopPropagation');
        $namedEventMock->expects($this->never())->method('setResponse');
        static::getContainer()->get(TransferToAccountSubscriber::class)->handle($namedEventMock);
    }

    public function testHandleReturnWhenEventNotSupported(): void
    {
        $namedEventMock = $this->getMockBuilder(NamedEvent::class)
            ->setConstructorArgs(['name', '123', '{"name": "name", "uid": "123"}'])
            ->getMock();

        $namedEventMock->expects($this->once())->method('isPropagationStopped')->willReturn(false);
        $namedEventMock->expects($this->never())->method('stopPropagation');
        $namedEventMock->expects($this->never())->method('setResponse');
        static::getContainer()->get(TransferToAccountSubscriber::class)->handle($namedEventMock);
    }

    public function testHandleResponseWhenParsingError(): void
    {
        $testData = [
            [
                'namedEvent' => new NamedEvent('account.transfer-to', '123', 'invalid_json'),
                'expectedResponse' => '{"success":false,"event_name":"account.transfer-to","event_uid":"123","errors":["Could not decode JSON, syntax error - malformed JSON."]}',
            ],
            [
                'namedEvent' => new NamedEvent('account.transfer-to', '123', '{"name": "account.transfer-to", "uid": "123"}'),
                'expectedResponse' => '{"success":false,"event_name":"account.transfer-to","event_uid":"123","errors":["accountIdFrom: This value should not be blank.","accountIdTo: This value should not be blank.","sum: This value should not be blank."]}',
            ],
        ];

        foreach ($testData as $item) {
            $eventSubscriber = static::getContainer()->get(TransferToAccountSubscriber::class);
            $namedEvent = $item['namedEvent'];
            $eventSubscriber->handle($namedEvent);
            $this->assertEquals($item['expectedResponse'], $namedEvent->getResponse());
        }
    }

    public function testHandleResponseWhenTransferringError(): void
    {
        $namedEvent = new NamedEvent('account.transfer-to', '123', '{"name": "account.transfer-to", "uid": "123", "account_id_from": "1", "account_id_to": "2", "sum": 123.0}');

        $accountOperationsInterfaceMock = $this->createMock(AccountOperationsInterface::class);
        $accountOperationsInterfaceMock
            ->expects($this->once())
            ->method('transferTo')
            ->with('1', '2', 123.0)
            ->willThrowException(new AccountNotFoundException('Some error'))
        ;

        $container = static::getContainer();
        $transferToAccountSubscriber = new TransferToAccountSubscriber(
            $container->get(ParserInterface::class),
            $accountOperationsInterfaceMock,
            $container->get(ResponseCreatorInterface::class)
        );

        $transferToAccountSubscriber->handle($namedEvent);

        $this->assertEquals('{"success":false,"event_name":"account.transfer-to","event_uid":"123","errors":["Some error"]}', $namedEvent->getResponse());
    }

    public function testHandleResponseWhenTransferringSuccessfully(): void
    {
        $namedEvent = new NamedEvent('account.transfer-to', '123', '{"name": "account.transfer-to", "uid": "123", "account_id_from": "1", "account_id_to": "2", "sum": 123.0}');

        $accountOperationsInterfaceMock = $this->createMock(AccountOperationsInterface::class);
        $accountOperationsInterfaceMock
            ->expects($this->once())
            ->method('transferTo')
            ->with('1', '2', 123.0)
        ;

        $container = static::getContainer();
        $transferToAccountSubscriber = new TransferToAccountSubscriber(
            $container->get(ParserInterface::class),
            $accountOperationsInterfaceMock,
            $container->get(ResponseCreatorInterface::class)
        );

        $transferToAccountSubscriber->handle($namedEvent);

        $this->assertEquals('{"success":true,"event_name":"account.transfer-to","event_uid":"123","data":[]}', $namedEvent->getResponse());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(['named.event' => 'handle'], TransferToAccountSubscriber::getSubscribedEvents());
    }
}