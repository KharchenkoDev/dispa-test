<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513114919 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE inn_lookups (id INT AUTO_INCREMENT NOT NULL, inn VARCHAR(12) NOT NULL, name VARCHAR(512) NOT NULL, is_active TINYINT NOT NULL, okved VARCHAR(20) NOT NULL, okved_name VARCHAR(512) NOT NULL, raw_response JSON NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_616F6E85E93323CB (inn), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE inn_lookups');
    }
}
