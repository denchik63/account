<?php

namespace App\Infrastructure\Db\Postgres;

use App\Infrastructure\Db\Postgres\Model\BalanceManipulationData;
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
            $balanceManipulationData = new BalanceManipulationData((int) $accountId, (float) $account['balance'], $sum);
            $this->doBalanceManipulation($balanceManipulationData);
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
            $balanceManipulationData = new BalanceManipulationData((int) $accountId, (float) $account['balance'], $sum);
            $this->doBalanceManipulation($balanceManipulationData);
        });
    }

    public function transferTo(string $accountIdFrom, string $accountIdTo, float $sum): void
    {
        $accountIdFromAsInt = (int) $accountIdFrom;
        $accountIdToAsInt = (int) $accountIdTo;
        $this->db->wrapInTransaction(function () use ($accountIdFromAsInt, $accountIdToAsInt, $sum): void {
            $accounts = $this->selectAccountsForUpdate($accountIdFromAsInt, $accountIdToAsInt);
            if (!isset($accounts[$accountIdFromAsInt])) {
                throw $this->createAAccountNotFoundException($accountIdFromAsInt);
            }
            if (!isset($accounts[$accountIdToAsInt])) {
                throw $this->createAAccountNotFoundException($accountIdToAsInt);
            }

            $accountFrom = $accounts[$accountIdFromAsInt];
            $balanceOnAccountFrom = (float) $accountFrom['balance'];
            if ($balanceOnAccountFrom < $sum) {
                throw new AccountHasNoEnoughMoneyException(sprintf('Account from (id=%s) has "%f", but need "%f"', $accountIdFromAsInt, $balanceOnAccountFrom, $sum));
            }

            $balanceManipulationDataForAccountFrom = new BalanceManipulationData($accountIdFromAsInt, $balanceOnAccountFrom, -$sum, null, $accountIdToAsInt);
            $this->doBalanceManipulation($balanceManipulationDataForAccountFrom);

            $accountTo = $accounts[$accountIdToAsInt];
            $balanceOnAccountTo = (float) $accountTo['balance'];
            $balanceManipulationDataForAccountTo = new BalanceManipulationData($accountIdToAsInt, $balanceOnAccountTo, $sum, $accountIdFromAsInt);
            $this->doBalanceManipulation($balanceManipulationDataForAccountTo);
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
            throw $this->createAAccountNotFoundException($accountId);
        }

        return $result[0];
    }

    private function doBalanceManipulation(BalanceManipulationData $data): void
    {
        $connection = $this->db->getConnection();

        $statement = $connection->prepare('UPDATE account SET balance = balance + :value WHERE id = :id');
        $statement->execute([
            'id' => $data->accountId,
            'value' => $data->sum,
        ]);

        $statement = $connection->prepare(<<<SQL
            INSERT INTO account_transaction (account_id, old_value, value, new_value, account_id_from, account_id_to) 
            VALUES (:account_id, :old_value, :value, :new_value, :account_id_from, :account_id_to)
            SQL
        );
        $statement->execute([
            'account_id' => $data->accountId,
            'old_value' => $data->currentBalance,
            'value' => $data->sum,
            'new_value' => $data->currentBalance + $data->sum,
            'account_id_from' => $data->accountFrom,
            'account_id_to' => $data->accountTo,
        ]);
    }

    /**
     * @return array<int, array{id: int, balance: string}>
     *
     * @throws AccountNotFoundException
     */
    private function selectAccountsForUpdate(int $accountIdFrom, int $accountIdTo): array
    {
        $statement = $this->db->getConnection()->prepare('SELECT id, balance FROM account WHERE id IN (?, ?) FOR NO KEY UPDATE');
        $statement->execute([$accountIdFrom, $accountIdTo]);

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return [];
        }

        $resultByIdAsKey = [];
        foreach ($result as $item) {
            $resultByIdAsKey[$item['id']] = $item;
        }

        return $resultByIdAsKey;
    }

    private function createAAccountNotFoundException(int $accountId): AccountNotFoundException
    {
        return new AccountNotFoundException(sprintf('Account with id %s not found', $accountId));
    }
}
