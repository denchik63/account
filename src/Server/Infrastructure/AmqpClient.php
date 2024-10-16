<?php

namespace App\Server\Infrastructure;

class AmqpClient
{
    public function __construct(private readonly AMQPStreamConnection $connection)
    {

    }
}