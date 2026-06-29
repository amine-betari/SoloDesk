<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create expense table for tracked company expenses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE expense (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, label VARCHAR(255) NOT NULL, amount NUMERIC(10, 2) NOT NULL, currency VARCHAR(3) NOT NULL, category VARCHAR(40) NOT NULL, supplier VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, spent_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2D3A8DA979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE expense ADD CONSTRAINT FK_2D3A8DA979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE expense DROP FOREIGN KEY FK_2D3A8DA979B1AD6');
        $this->addSql('DROP TABLE expense');
    }
}
