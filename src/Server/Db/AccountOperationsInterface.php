<?php

namespace App\Server\Db;

use App\Server\Db\Exception\ExceptionInterface;

interface AccountOperationsInterface
{
    /** @throws ExceptionInterface */
    public function refill(string $accountId, float $sum): void;

    /** @throws ExceptionInterface */
    public function writeOff(string $accountId, float $sum): void;
}
