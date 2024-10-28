<?php

namespace App\Server\Parser\Exception;

interface ExceptionInterface extends \Throwable
{
    /** @return list<string> */
    public function getErrors(): array;
}
