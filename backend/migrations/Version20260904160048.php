<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904160048 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create cv table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE cv (
            id SERIAL NOT NULL,
            title VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            upload_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            analysis TEXT DEFAULT NULL,
            user_id INT NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX IDX_B66FFE92A76ED395 ON cv (user_id)');

        $this->addSql('ALTER TABLE cv
            ADD CONSTRAINT FK_B66FFE92A76ED395
            FOREIGN KEY (user_id)
            REFERENCES "user" (id)
            NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cv DROP CONSTRAINT FK_B66FFE92A76ED395');
        $this->addSql('DROP TABLE cv');
    }
}