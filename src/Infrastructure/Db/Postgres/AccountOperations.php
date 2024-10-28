<?php

namespace App\Infrastructure\Db\Postgres;

use App\Server\Db\AccountOperationsInterface;
use App\Server\Db\Exception\AccountHasNoEnoughMoneyException;
use App\Server\Db\Exception\AccountNotFoundException;

class AccountOperations implements AccountOperationsInterface
{
    public function __construct(private readonly Db $db)
    {
    }

    public function refill(string $accountId, float $sum): void
    {
        $this->db->wrapInTransaction(function () use ($accountId, $sum): void {
            $account = $this->selectAccountForUpdate((int) $accountId);
            $this->doBalanceManipulation((int) $accountId, (float) $account['balance'], $sum);
        });
    }

    public function writeOff(string $accountId, float $sum): void
    {
        $this->db->wrapInTransaction(function () use ($accountId, $sum): void {
            $account = $this->selectAccountForUpdate((int) $accountId);
            $currentBalance = (float) $account['balance'];
            $absoluteSum = abs($sum);
            if ($currentBalance < $absoluteSum) {
                throw new AccountHasNoEnoughMoneyException(sprintf('Account has "%f", but need "%f"', $currentBalance, $absoluteSum));
            }
            $this->doBalanceManipulation((int) $accountId, $currentBalance, $sum);
        });
    }

    /**
     * @return array{id: int, balance: string}
     *
     * @throws AccountNotFoundException
     */
    private function selectAccountForUpdate(int $accountId): array
    {
        $statement = $this->db->getConnection()->prepare('SELECT id, balance FROM account WHERE id = :id FOR NO KEY UPDATE');
        $statement->execute(['id' => $accountId]);

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (!isset($result[0])) {
            throw new AccountNotFoundException(sprintf('Account with id %s not found', $accountId));
        }

        return $result[0];
    }

    private function doBalanceManipulation(int $accountId, float $currentBalance, float $sum): void
    {
        $connection = $this->db->getConnection();

        $statement = $connection->prepare('UPDATE account SET balance = balance + :value WHERE id = :id');
        $statement->execute([
            'id' => $accountId,
            'value' => $sum,
        ]);

        $statement = $connection->prepare(
            'INSERT INTO account_transaction (account_id, old_value, value, new_value) VALUES (:account_id, :old_value, :value, :new_value)'
        );
        $statement->execute([
            'account_id' => (int) $accountId,
            'old_value' => $currentBalance,
            'value' => $sum,
            'new_value' => $currentBalance + $sum,
        ]);
    }
}
