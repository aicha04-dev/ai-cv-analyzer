<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904160414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE job (
            id SERIAL NOT NULL,
            title VARCHAR(255) NOT NULL,
            company VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            location VARCHAR(255) DEFAULT NULL,
            skills TEXT DEFAULT NULL,
            create_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE job');
    }
}