<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250712140206 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sales_document (id INT AUTO_INCREMENT NOT NULL, project_id INT DEFAULT NULL, estimate_id INT DEFAULT NULL, type VARCHAR(100) NOT NULL, reference VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', modified_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_95D51252166D1F9C (project_id), INDEX IDX_95D5125285F23082 (estimate_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sales_document_item (id INT AUTO_INCREMENT NOT NULL, sales_document_id INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, unit_price NUMERIC(10, 2) DEFAULT NULL, quantity NUMERIC(10, 3) DEFAULT NULL, line_total NUMERIC(10, 2) DEFAULT NULL, INDEX IDX_7D89DF3E873469F3 (sales_document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sales_document ADD CONSTRAINT FK_95D51252166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE sales_document ADD CONSTRAINT FK_95D5125285F23082 FOREIGN KEY (estimate_id) REFERENCES estimate (id)');
        $this->addSql('ALTER TABLE sales_document_item ADD CONSTRAINT FK_7D89DF3E873469F3 FOREIGN KEY (sales_document_id) REFERENCES sales_document (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sales_document DROP FOREIGN KEY FK_95D51252166D1F9C');
        $this->addSql('ALTER TABLE sales_document DROP FOREIGN KEY FK_95D5125285F23082');
        $this->addSql('ALTER TABLE sales_document_item DROP FOREIGN KEY FK_7D89DF3E873469F3');
        $this->addSql('DROP TABLE sales_document');
        $this->addSql('DROP TABLE sales_document_item');
    }
}
