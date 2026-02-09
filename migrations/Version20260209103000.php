<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add company legal and contact fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company ADD ice VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD fiscal_id VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD tax_professional VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD address LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD city VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD country VARCHAR(120) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD phone VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD email VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE company ADD logo_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company DROP logo_path');
        $this->addSql('ALTER TABLE company DROP email');
        $this->addSql('ALTER TABLE company DROP phone');
        $this->addSql('ALTER TABLE company DROP country');
        $this->addSql('ALTER TABLE company DROP city');
        $this->addSql('ALTER TABLE company DROP address');
        $this->addSql('ALTER TABLE company DROP tax_professional');
        $this->addSql('ALTER TABLE company DROP fiscal_id');
        $this->addSql('ALTER TABLE company DROP ice');
    }
}
