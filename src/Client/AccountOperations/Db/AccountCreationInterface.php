<?php

namespace App\Client\AccountOperations\Db;

interface AccountCreationInterface
{
    public function create(float $initialBalance): string;
}