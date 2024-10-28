<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241028200824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds "account_id_from" and "account_id_to" fields to account_transaction';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_transaction ADD COLUMN account_id_from INT DEFAULT NULL');
        $this->addSql('ALTER TABLE account_transaction ADD COLUMN account_id_to INT DEFAULT NULL');
        $this->addSql('ALTER TABLE account_transaction ADD CONSTRAINT FK_ACCOUNT_FROM FOREIGN KEY (account_id_from) REFERENCES account (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE account_transaction ADD CONSTRAINT FK_ACCOUNT_TO FOREIGN KEY (account_id_to) REFERENCES account (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_transaction DROP CONSTRAINT FK_ACCOUNT_FROM');
        $this->addSql('ALTER TABLE account_transaction DROP CONSTRAINT FK_ACCOUNT_TO');
        $this->addSql('ALTER TABLE account_transaction DROP COLUMN account_id_from');
        $this->addSql('ALTER TABLE account_transaction DROP COLUMN account_id_to');
    }
}
