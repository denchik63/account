<?php

namespace App\Server\Response\Model;

abstract class BaseResponse
{
    public function __construct(public readonly bool $success)
    {
    }
}
