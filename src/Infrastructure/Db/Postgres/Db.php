<?php

namespace App\Infrastructure\Db\Postgres;

class Db
{
    private ?\PDO $connection;

    public function __construct(
        private readonly string $host,
        private readonly string $port,
        private readonly string $database,
        private readonly string $user,
        private readonly string $password,
    ) {
        $this->connection = null;
    }

    public function getConnection(): \PDO
    {
        if (null === $this->connection) {
            $this->connection = new \PDO(
                sprintf('pgsql:host=%s;port=%s;dbname=%s', $this->host, $this->port, $this->database),
                $this->user,
                $this->password
            );
        }

        return $this->connection;
    }

    public function wrapInTransaction(callable $function): void
    {
        $this->beginTransaction();

        try {
            $function($this->getConnection());
            $this->commit();
        } catch (\Throwable $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    private function beginTransaction(): void
    {
        $connection = $this->getConnection();
        $connection->query('BEGIN TRANSACTION');
    }

    private function commit(): void
    {
        $connection = $this->getConnection();
        $connection->query('COMMIT');
    }

    private function rollback(): void
    {
        $connection = $this->getConnection();
        $connection->query('ROLLBACK');
    }
}
