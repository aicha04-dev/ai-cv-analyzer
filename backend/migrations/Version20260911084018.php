<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260911084018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job ADD country VARCHAR(100) DEFAULT NULL, ADD category VARCHAR(100) DEFAULT NULL, ADD salary_min DOUBLE PRECISION DEFAULT NULL, ADD salary_max DOUBLE PRECISION DEFAULT NULL, ADD experience_level VARCHAR(50) DEFAULT NULL, CHANGE source_url source_url VARCHAR(2048) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job DROP country, DROP category, DROP salary_min, DROP salary_max, DROP experience_level, CHANGE source_url source_url LONGTEXT DEFAULT NULL');
    }
}
