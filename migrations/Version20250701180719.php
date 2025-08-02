<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250701180719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE estimate ADD project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE estimate ADD CONSTRAINT FK_D2EA4607166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D2EA4607166D1F9C ON estimate (project_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE estimate DROP FOREIGN KEY FK_D2EA4607166D1F9C');
        $this->addSql('DROP INDEX UNIQ_D2EA4607166D1F9C ON estimate');
        $this->addSql('ALTER TABLE estimate DROP project_id');
    }
}
