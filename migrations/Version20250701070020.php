<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250701070020 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE estimate ADD vat_rate NUMERIC(4, 2) DEFAULT NULL, CHANGE amount amount NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD vat_rate NUMERIC(4, 2) DEFAULT NULL, ADD amount NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP vat_rate, DROP amount');
        $this->addSql('ALTER TABLE estimate DROP vat_rate, CHANGE amount amount DOUBLE PRECISION DEFAULT NULL');
    }
}
