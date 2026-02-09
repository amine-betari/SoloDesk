<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add company table and company_id to tenant-scoped entities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_USER_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_USER_COMPANY ON user (company_id)');

        $this->addSql('ALTER TABLE client ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE client ADD CONSTRAINT FK_CLIENT_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_CLIENT_COMPANY ON client (company_id)');

        $this->addSql('ALTER TABLE project ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_PROJECT_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_PROJECT_COMPANY ON project (company_id)');

        $this->addSql('ALTER TABLE estimate ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE estimate ADD CONSTRAINT FK_ESTIMATE_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_ESTIMATE_COMPANY ON estimate (company_id)');

        $this->addSql('ALTER TABLE sales_document ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sales_document ADD CONSTRAINT FK_SALES_DOCUMENT_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_SALES_DOCUMENT_COMPANY ON sales_document (company_id)');

        $this->addSql('ALTER TABLE sales_document_item ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sales_document_item ADD CONSTRAINT FK_SALES_DOCUMENT_ITEM_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_SALES_DOCUMENT_ITEM_COMPANY ON sales_document_item (company_id)');

        $this->addSql('ALTER TABLE payment ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_PAYMENT_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_PAYMENT_COMPANY ON payment (company_id)');

        $this->addSql('ALTER TABLE document ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_DOCUMENT_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_DOCUMENT_COMPANY ON document (company_id)');

        $this->addSql('ALTER TABLE document_template ADD company_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE document_template ADD CONSTRAINT FK_DOCUMENT_TEMPLATE_COMPANY FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_DOCUMENT_TEMPLATE_COMPANY ON document_template (company_id)');

        $this->addSql('INSERT INTO company (name, created_at) VALUES (\'Default Company\', NOW())');
        $this->addSql('SET @company_id = LAST_INSERT_ID()');
        $this->addSql('UPDATE user SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE client SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE project SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE estimate SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE sales_document SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE sales_document_item SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE payment SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE document SET company_id = @company_id WHERE company_id IS NULL');
        $this->addSql('UPDATE document_template SET company_id = @company_id WHERE company_id IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_template DROP FOREIGN KEY FK_DOCUMENT_TEMPLATE_COMPANY');
        $this->addSql('DROP INDEX IDX_DOCUMENT_TEMPLATE_COMPANY ON document_template');
        $this->addSql('ALTER TABLE document_template DROP company_id');

        $this->addSql('ALTER TABLE document DROP FOREIGN KEY FK_DOCUMENT_COMPANY');
        $this->addSql('DROP INDEX IDX_DOCUMENT_COMPANY ON document');
        $this->addSql('ALTER TABLE document DROP company_id');

        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_PAYMENT_COMPANY');
        $this->addSql('DROP INDEX IDX_PAYMENT_COMPANY ON payment');
        $this->addSql('ALTER TABLE payment DROP company_id');

        $this->addSql('ALTER TABLE sales_document_item DROP FOREIGN KEY FK_SALES_DOCUMENT_ITEM_COMPANY');
        $this->addSql('DROP INDEX IDX_SALES_DOCUMENT_ITEM_COMPANY ON sales_document_item');
        $this->addSql('ALTER TABLE sales_document_item DROP company_id');

        $this->addSql('ALTER TABLE sales_document DROP FOREIGN KEY FK_SALES_DOCUMENT_COMPANY');
        $this->addSql('DROP INDEX IDX_SALES_DOCUMENT_COMPANY ON sales_document');
        $this->addSql('ALTER TABLE sales_document DROP company_id');

        $this->addSql('ALTER TABLE estimate DROP FOREIGN KEY FK_ESTIMATE_COMPANY');
        $this->addSql('DROP INDEX IDX_ESTIMATE_COMPANY ON estimate');
        $this->addSql('ALTER TABLE estimate DROP company_id');

        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_PROJECT_COMPANY');
        $this->addSql('DROP INDEX IDX_PROJECT_COMPANY ON project');
        $this->addSql('ALTER TABLE project DROP company_id');

        $this->addSql('ALTER TABLE client DROP FOREIGN KEY FK_CLIENT_COMPANY');
        $this->addSql('DROP INDEX IDX_CLIENT_COMPANY ON client');
        $this->addSql('ALTER TABLE client DROP company_id');

        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_USER_COMPANY');
        $this->addSql('DROP INDEX IDX_USER_COMPANY ON user');
        $this->addSql('ALTER TABLE user DROP company_id');

        $this->addSql('DROP TABLE company');
    }
}
