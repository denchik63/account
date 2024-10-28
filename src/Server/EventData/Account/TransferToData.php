<?php

namespace App\Server\EventData\Account;

use App\Server\EventData\BaseData;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class TransferToData extends BaseData
{
    private const float MAX_SUM = 100000000.0;

    public function __construct(
        public readonly ?string $accountIdFrom,
        public readonly ?string $accountIdTo,
        public readonly ?float $sum,
    ) {
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('accountIdFrom', new Assert\NotBlank());
        $metadata->addPropertyConstraint('accountIdFrom', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('accountIdTo', new Assert\NotBlank());
        $metadata->addPropertyConstraint('accountIdTo', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('sum', new Assert\NotBlank());
        $metadata->addPropertyConstraint('sum', new Assert\GreaterThan(['value' => 0]));
        $metadata->addPropertyConstraint('sum', new Assert\LessThanOrEqual(['value' => self::MAX_SUM]));
    }
}
