<?php

namespace App\Server\EventData\Base;

use App\Server\EventData\BaseData;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Event extends BaseData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $uid,
    ) {
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank());
        $metadata->addPropertyConstraint('name', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('uid', new Assert\NotBlank());
        $metadata->addPropertyConstraint('uid', new Assert\Length(['max' => 255]));
    }
}
