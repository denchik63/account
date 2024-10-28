<?php

declare(strict_types=1);

namespace App\Tests\Server\Tests\Parser;

use App\Server\EventData\Account\RefillData;
use App\Server\EventData\Account\WriteOffData;
use App\Server\EventData\Base\Event;
use App\Server\Parser\Exception\EventValidationException;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ValidationRulesTest extends WebTestCase
{
    public function testEvent(): void
    {
        $testData = [
            [
                'event' => new Event(null, null),
                'errorMessages' => [
                    'name: This value should not be blank.',
                    'uid: This value should not be blank.',
                ],
            ],
            [
                'event' => new Event('', ''),
                'errorMessages' => [
                    'name: This value should not be blank.',
                    'uid: This value should not be blank.',
                ],
            ],
            [
                'event' => new Event(str_repeat('a', 256), str_repeat('b', 256)),
                'errorMessages' => [
                    'name: This value is too long. It should have 255 characters or less.',
                    'uid: This value is too long. It should have 255 characters or less.',
                ],
            ],
            [
                'event' => new Event('name', 'uid'),
                'errorMessages' => [],
            ],
        ];

        $this->assertValidation($testData);
    }

    public function testRefillData(): void
    {
        $testData = [
            [
                'event' => new RefillData(null, null),
                'errorMessages' => [
                    'accountId: This value should not be blank.',
                    'sum: This value should not be blank.',
                ],
            ],
            [
                'event' => new RefillData('', 12.0),
                'errorMessages' => [
                    'accountId: This value should not be blank.',
                ],
            ],
            [
                'event' => new RefillData(str_repeat('a', 256), 11.0),
                'errorMessages' => [
                    'accountId: This value is too long. It should have 255 characters or less.',
                ],
            ],
            [
                'event' => new RefillData('accountId', -0.1),
                'errorMessages' => [
                    'sum: This value should be greater than 0.',
                ],
            ],
            [
                'event' => new RefillData('accountId', 100000000.1),
                'errorMessages' => [
                    'sum: This value should be less than or equal to 100000000.',
                ],
            ],
            [
                'event' => new RefillData('123', 21.0),
                'errorMessages' => [],
            ],
        ];

        $this->assertValidation($testData);
    }

    public function testWriteOffData(): void
    {
        $testData = [
            [
                'event' => new WriteOffData(null, null),
                'errorMessages' => [
                    'accountId: This value should not be blank.',
                    'sum: This value should not be blank.',
                ],
            ],
            [
                'event' => new WriteOffData('', -12.0),
                'errorMessages' => [
                    'accountId: This value should not be blank.',
                ],
            ],
            [
                'event' => new WriteOffData(str_repeat('a', 256), -11.0),
                'errorMessages' => [
                    'accountId: This value is too long. It should have 255 characters or less.',
                ],
            ],
            [
                'event' => new WriteOffData('accountId', 0.1),
                'errorMessages' => [
                    'sum: This value should be less than 0.',
                ],
            ],
            [
                'event' => new WriteOffData('accountId', -100000000.1),
                'errorMessages' => [
                    'sum: This value should be greater than or equal to -100000000.',
                ],
            ],
            [
                'event' => new WriteOffData('123', -21.0),
                'errorMessages' => [],
            ],
        ];

        $this->assertValidation($testData);
    }

    /** @param array<string, mixed> $testData */
    private function assertValidation(array $testData): void
    {
        $validator = static::getContainer()->get(ValidatorInterface::class);
        foreach ($testData as $item) {
            $constraintViolationList = $validator->validate($item['event']);
            $eventValidationException = new EventValidationException($constraintViolationList);
            $this->assertEquals($item['errorMessages'], $eventValidationException->getErrors());
        }
    }
}
