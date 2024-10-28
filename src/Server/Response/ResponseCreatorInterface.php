<?php

namespace App\Server\Response;

use App\Server\Response\Model\BaseResponse;

interface ResponseCreatorInterface
{
    public function createResponse(BaseResponse $response): string;
}
