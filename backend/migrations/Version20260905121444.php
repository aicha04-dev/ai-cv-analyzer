<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260905121444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_match (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, analysis LONGTEXT DEFAULT NULL, cv_id INT NOT NULL, job_id INT NOT NULL, INDEX IDX_7B077875CFE419E2 (cv_id), INDEX IDX_7B077875BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE job_match ADD CONSTRAINT FK_7B077875CFE419E2 FOREIGN KEY (cv_id) REFERENCES cv (id)');
        $this->addSql('ALTER TABLE job_match ADD CONSTRAINT FK_7B077875BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_match DROP FOREIGN KEY FK_7B077875CFE419E2');
        $this->addSql('ALTER TABLE job_match DROP FOREIGN KEY FK_7B077875BE04EA9');
        $this->addSql('DROP TABLE job_match');
    }
}
