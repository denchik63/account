<?php

namespace App\Server\EventData\Account;

use App\Server\EventData\BaseData;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class WriteOffData extends BaseData
{
    private const float MAX_SUM = -100000000.0;

    public function __construct(
        public readonly ?string $accountId,
        public readonly ?float $sum,
    ) {
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('accountId', new Assert\NotBlank());
        $metadata->addPropertyConstraint('accountId', new Assert\Length(['max' => 255]));
        $metadata->addPropertyConstraint('sum', new Assert\NotBlank());
        $metadata->addPropertyConstraint('sum', new Assert\LessThan(['value' => 0]));
        $metadata->addPropertyConstraint('sum', new Assert\GreaterThanOrEqual(['value' => self::MAX_SUM]));
    }
}
