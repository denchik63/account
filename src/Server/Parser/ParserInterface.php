<?php

namespace App\Server\Parser;

use App\Server\EventData\BaseData;
use App\Server\Parser\Exception\ExceptionInterface;

interface ParserInterface
{
    /** @throws ExceptionInterface */
    public function parse(string $data, string $dataClass): BaseData;
}
