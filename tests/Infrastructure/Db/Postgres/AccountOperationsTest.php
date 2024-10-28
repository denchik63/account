<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Db\Postgres;

use App\Infrastructure\Db\Postgres\AccountOperations;
use App\Infrastructure\Db\Postgres\Db;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountOperationsTest extends WebTestCase
{
    public function testRefill(): void
    {
        $this->clearDbData();

        $accountId = $this->createAccount(0.0);
        $accountOperations = static::getContainer()->get(AccountOperations::class);
        $accountOperations->refill((string) $accountId, 150.0);

        $balance = $this->getAccountBalance($accountId);
        $this->assertEquals(150.0, $balance);

        $accountTransactions = $this->getAccountTransactions($accountId);
        $this->assertCount(1, $accountTransactions);
        $accountTransaction = $accountTransactions[0];
        $this->assertEquals(0.0, $accountTransaction['old_value']);
        $this->assertEquals(150.0, $accountTransaction['new_value']);
        $this->assertEquals(150.0, $accountTransaction['value']);
    }

    public function testWriteOff(): void
    {
        $this->clearDbData();

        $accountId = $this->createAccount(200.0);
        $accountOperations = static::getContainer()->get(AccountOperations::class);
        $accountOperations->writeOff((string) $accountId, -150.0);

        $balance = $this->getAccountBalance($accountId);
        $this->assertEquals(50.0, $balance);

        $accountTransactions = $this->getAccountTransactions($accountId);
        $this->assertCount(1, $accountTransactions);
        $accountTransaction = $accountTransactions[0];
        $this->assertEquals(200.0, $accountTransaction['old_value']);
        $this->assertEquals(50.0, $accountTransaction['new_value']);
        $this->assertEquals(-150.0, $accountTransaction['value']);
    }

    private function clearDbData(): void
    {
        $db = static::getContainer()->get(Db::class);
        $connection = $db->getConnection();

        $connection->exec('DELETE FROM account_transaction');
        $connection->exec('DELETE FROM account');
    }

    private function createAccount(float $balance): int
    {
        $db = static::getContainer()->get(Db::class);
        $connection = $db->getConnection();
        $statement = $connection->prepare('INSERT INTO account (balance) VALUES (:balance) RETURNING id');
        $statement->execute(['balance' => $balance]);

        return $statement->fetch()['id'];
    }

    private function getAccountBalance(int $accountId): float
    {
        $db = static::getContainer()->get(Db::class);
        $connection = $db->getConnection();
        $statement = $connection->prepare('SELECT balance FROM account WHERE id = :id');
        $statement->execute(['id' => $accountId]);

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (!isset($result[0])) {
            throw new \RuntimeException(sprintf('Account with id %s not found', $accountId));
        }

        return (float) $result[0]['balance'];
    }

    private function getAccountTransactions(int $accountId): array
    {
        $db = static::getContainer()->get(Db::class);
        $connection = $db->getConnection();
        $statement = $connection->prepare('SELECT * FROM account_transaction WHERE account_id = :account_id');
        $statement->execute(['account_id' => $accountId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}