<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513173348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove okved_name column from inn_lookups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inn_lookups DROP COLUMN okved_name');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE inn_lookups ADD okved_name VARCHAR(512) NOT NULL DEFAULT \'\'');
    }
}
