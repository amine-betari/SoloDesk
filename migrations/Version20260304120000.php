<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create collaborator table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE collaborator (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, name VARCHAR(255) NOT NULL, role VARCHAR(255) DEFAULT NULL, type VARCHAR(30) NOT NULL, monthly_cost DOUBLE PRECISION DEFAULT NULL, skills LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_4E0399F979D1C50F (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE collaborator ADD CONSTRAINT FK_4E0399F979D1C50F FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collaborator DROP FOREIGN KEY FK_4E0399F979D1C50F');
        $this->addSql('DROP TABLE collaborator');
    }
}
