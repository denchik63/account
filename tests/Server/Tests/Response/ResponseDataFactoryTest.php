<?php

declare(strict_types=1);

namespace App\Tests\Server\Tests\Response;

use App\Server\Response\ResponseDataFactory;
use PHPUnit\Framework\TestCase;

class ResponseDataFactoryTest extends TestCase
{
    public function testCreateErrorResponseData(): void
    {
        $data = ResponseDataFactory::createErrorResponseData('name', 'uid', ['some-error']);
        $this->assertEquals('name', $data->eventName);
        $this->assertEquals('uid', $data->eventUid);
        $this->assertEquals(['some-error'], $data->errors);
    }

    public function testCreateSuccessResponseData(): void
    {
        $data = ResponseDataFactory::createSuccessResponseData('name', 'uid', ['some-data']);
        $this->assertEquals('name', $data->eventName);
        $this->assertEquals('uid', $data->eventUid);
        $this->assertEquals(['some-data'], $data->data);
    }
}
