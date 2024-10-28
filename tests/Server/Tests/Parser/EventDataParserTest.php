<?php

declare(strict_types=1);

namespace App\Tests\Server\Tests\Parser;

use App\Server\EventData\Account\RefillData;
use App\Server\EventData\Base\Event;
use App\Server\Parser\EventDataParser;
use App\Server\Parser\Exception\EventParsingException;
use App\Server\Parser\Exception\EventValidationException;
use JMS\Serializer\Exception\RuntimeException as SerializerException;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventDataParserTest extends TestCase
{
    public function testParseSerializationException(): void
    {
        $serializerMock = $this->createMock(SerializerInterface::class);
        $serializerMock
            ->expects($this->once())
            ->method('deserialize')
            ->with('{"key": "value"}', Event::class, 'json')
            ->willThrowException(new SerializerException('Some exception'))
        ;

        $this->expectException(EventParsingException::class);
        $this->expectExceptionMessage('Some exception');

        $eventDataParser = $this->createEventDataParser($serializerMock);
        $eventDataParser->parse('{"key": "value"}', Event::class);
    }

    public function testParseValidationException(): void
    {
        $event = new Event('name', 'uid');

        $serializerMock = $this->createMock(SerializerInterface::class);
        $serializerMock
            ->expects($this->once())
            ->method('deserialize')
            ->with('{"key": "value"}', Event::class, 'json')
            ->willReturn($event)
        ;

        $constraintViolationListMock = $this->createMock(ConstraintViolationList::class);
        $constraintViolationListMock->expects($this->once())->method('count')->willReturn(1);
        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($event)
            ->willReturn($constraintViolationListMock)
        ;

        $this->expectException(EventValidationException::class);
        $this->expectExceptionMessage('');

        $eventDataParser = $this->createEventDataParser($serializerMock, $validatorMock);
        $eventDataParser->parse('{"key": "value"}', Event::class);
    }

    public function testSuccessfulParse(): void
    {
        $refillData = new RefillData('2', 200.0);

        $serializerMock = $this->createMock(SerializerInterface::class);
        $serializerMock
            ->expects($this->once())
            ->method('deserialize')
            ->with('{"key": "value"}', RefillData::class, 'json')
            ->willReturn($refillData)
        ;

        $constraintViolationListMock = $this->createMock(ConstraintViolationListInterface::class);
        $constraintViolationListMock->expects($this->once())->method('count')->willReturn(0);
        $validatorMock = $this->createMock(ValidatorInterface::class);
        $validatorMock
            ->expects($this->once())
            ->method('validate')
            ->with($refillData)
            ->willReturn($constraintViolationListMock)
        ;

        $eventDataParser = $this->createEventDataParser($serializerMock, $validatorMock);
        $parsedRefillData = $eventDataParser->parse('{"key": "value"}', RefillData::class);
        $this->assertEquals($refillData, $parsedRefillData);
    }

    private function createEventDataParser(SerializerInterface $serializer, ?ValidatorInterface $validator = null): EventDataParser
    {
        return new EventDataParser($serializer, $validator ?? $this->createMock(ValidatorInterface::class), 'json');
    }
}
