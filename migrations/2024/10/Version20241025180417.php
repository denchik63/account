<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241025180417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates postgres db schema';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS account (id SERIAL NOT NULL, balance NUMERIC(10, 2), PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE IF NOT EXISTS account_transaction (id SERIAL NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, account_id INT NOT NULL, old_value NUMERIC(10, 2), value NUMERIC(10, 2), new_value NUMERIC(10, 2), PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE account_transaction ADD CONSTRAINT FK_ACCOUNT FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_transaction DROP CONSTRAINT FK_ACCOUNT');
        $this->addSql('DROP TABLE IF EXISTS account');
        $this->addSql('DROP TABLE IF EXISTS account_transaction');
    }
}
