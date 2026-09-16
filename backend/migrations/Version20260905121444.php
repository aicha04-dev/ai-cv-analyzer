<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905121444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create job_match table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE job_match (
            id SERIAL NOT NULL,
            score INT NOT NULL,
            analysis TEXT DEFAULT NULL,
            cv_id INT NOT NULL,
            job_id INT NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE INDEX IDX_7B077875CFE419E2 ON job_match (cv_id)');
        $this->addSql('CREATE INDEX IDX_7B077875BE04EA9 ON job_match (job_id)');

        $this->addSql('ALTER TABLE job_match
            ADD CONSTRAINT FK_7B077875CFE419E2
            FOREIGN KEY (cv_id)
            REFERENCES cv (id)
            NOT DEFERRABLE INITIALLY IMMEDIATE');

        $this->addSql('ALTER TABLE job_match
            ADD CONSTRAINT FK_7B077875BE04EA9
            FOREIGN KEY (job_id)
            REFERENCES job (id)
            NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_match DROP CONSTRAINT FK_7B077875CFE419E2');
        $this->addSql('ALTER TABLE job_match DROP CONSTRAINT FK_7B077875BE04EA9');
        $this->addSql('DROP TABLE job_match');
    }
}