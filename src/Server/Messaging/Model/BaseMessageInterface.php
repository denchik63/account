<?php

namespace App\Server\Messaging\Model;

interface BaseMessageInterface
{
    public function getData(): string;
}
