<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260910110427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add external_id and source_url to job';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job
            ADD external_id VARCHAR(255) DEFAULT NULL,
            ADD source_url TEXT DEFAULT NULL');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_FBD8E0F89F75D7B0
            ON job (external_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_FBD8E0F89F75D7B0');

        $this->addSql('ALTER TABLE job
            DROP external_id,
            DROP source_url');
    }
}