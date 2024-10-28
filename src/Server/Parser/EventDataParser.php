<?php

namespace App\Server\Parser;

use App\Server\EventData\BaseData;
use App\Server\Parser\Exception\EventParsingException;
use App\Server\Parser\Exception\EventValidationException;
use JMS\Serializer\Exception\Exception as SerializerException;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EventDataParser implements ParserInterface
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly string $dataFormat,
    ) {
    }

    public function parse(string $data, string $dataClass): BaseData
    {
        try {
            $object = $this->serializer->deserialize($data, $dataClass, $this->dataFormat);
        } catch (SerializerException $exception) {
            throw new EventParsingException($exception->getMessage());
        }

        $violations = $this->validator->validate($object);
        if ($violations->count() > 0) {
            throw new EventValidationException($violations);
        }

        return $object;
    }
}
