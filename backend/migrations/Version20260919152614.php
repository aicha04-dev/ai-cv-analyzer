<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260919152614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow CVs to be created without an authenticated user';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySQLPlatform) {
            $this->addSql(
                'ALTER TABLE cv MODIFY user_id INT DEFAULT NULL'
            );
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(
                'ALTER TABLE cv ALTER COLUMN user_id DROP NOT NULL'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySQLPlatform) {
            $this->addSql(
                'ALTER TABLE cv MODIFY user_id INT NOT NULL'
            );
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addSql(
                'ALTER TABLE cv ALTER COLUMN user_id SET NOT NULL'
            );
        }
    }
}