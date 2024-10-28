<?php

namespace App\Server\Parser\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

class EventValidationException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(private readonly ConstraintViolationListInterface $violationList)
    {
        parent::__construct();
    }

    public function getErrors(): array
    {
        $messages = [];
        foreach ($this->violationList as $violation) {
            $messages[] = $violation->getPropertyPath().': '.$violation->getMessage();
        }

        return $messages;
    }
}
