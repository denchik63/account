<?php

namespace App\Infrastructure\Db\Postgres;

use App\Client\AccountOperations\Db\AccountCreationInterface;

class AccountCreation implements AccountCreationInterface
{
    public function __construct(private readonly Db $db)
    {

    }

    public function create(float $initialBalance): string
    {
        $connection = $this->db->getConnection();
        $statement = $connection->prepare(<<<SQL
            INSERT INTO account (balance) 
            VALUES (:initialBalance)
            RETURNING id
            SQL
        );

        $statement->execute([
            'initialBalance' => $initialBalance,
        ]);

        $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            throw new \RuntimeException(sprintf('No id result, after inserting data'));
        }

        return (string) $result[0]['id'];
    }
}