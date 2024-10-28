<?php

namespace App\Server\Messaging\Model;

interface BaseMessageInterface
{
    public function getRequest(): string;
}
