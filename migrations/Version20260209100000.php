<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make company_id non-null on tenant-scoped tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET @company_id = (SELECT id FROM company ORDER BY id ASC LIMIT 1)');
        $this->addSql('UPDATE user SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE client SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE project SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE estimate SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE sales_document SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE sales_document_item SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE payment SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE document SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE document_template SET company_id = @company_id WHERE company_id IS NULL');

        $this->addSql('ALTER TABLE user MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE client MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE project MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE estimate MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE sales_document MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE sales_document_item MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE payment MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE document MODIFY company_id INT NOT NULL');
        $this->addSql('ALTER TABLE document_template MODIFY company_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_template MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sales_document_item MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sales_document MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE estimate MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client MODIFY company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user MODIFY company_id INT DEFAULT NULL');
    }
}
