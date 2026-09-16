<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911084018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add job search and salary fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job
            ADD country VARCHAR(100) DEFAULT NULL,
            ADD category VARCHAR(100) DEFAULT NULL,
            ADD salary_min DOUBLE PRECISION DEFAULT NULL,
            ADD salary_max DOUBLE PRECISION DEFAULT NULL,
            ADD experience_level VARCHAR(50) DEFAULT NULL');

        $this->addSql('ALTER TABLE job
            ALTER COLUMN source_url TYPE VARCHAR(2048)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job
            DROP country,
            DROP category,
            DROP salary_min,
            DROP salary_max,
            DROP experience_level');

        $this->addSql('ALTER TABLE job
            ALTER COLUMN source_url TYPE TEXT');
    }
}