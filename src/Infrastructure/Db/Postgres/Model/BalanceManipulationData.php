<?php

namespace App\Infrastructure\Db\Postgres\Model;

class BalanceManipulationData
{
    public function __construct(
        public readonly int $accountId,
        public readonly float $currentBalance,
        public readonly float $sum,
        public readonly ?int $accountFrom = null,
        public readonly ?int $accountTo = null,
    ) {

    }
}