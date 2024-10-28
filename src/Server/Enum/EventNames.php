<?php

namespace App\Server\Enum;

enum EventNames: string
{
    case AccountRefill = 'account.refill';
    case AccountWriteOff = 'account.write-off';
}
