<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add created_at to JobMatch.
 */
final class Version20260914171317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at to job_match';
    }

    public function up(Schema $schema): void
    {
        // 1. Add the column temporarily as nullable
        $this->addSql(
            'ALTER TABLE job_match ADD created_at DATETIME DEFAULT NULL'
        );

        // 2. Give existing matches a valid date
        $this->addSql(
            'UPDATE job_match SET created_at = NOW() WHERE created_at IS NULL'
        );

        // 3. Make the column required
        $this->addSql(
            'ALTER TABLE job_match MODIFY created_at DATETIME NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'ALTER TABLE job_match DROP created_at'
        );
    }
}