<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250810151832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sales_document ADD client_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE sales_document ADD CONSTRAINT FK_95D5125219EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('CREATE INDEX IDX_95D5125219EB6921 ON sales_document (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sales_document DROP FOREIGN KEY FK_95D5125219EB6921');
        $this->addSql('DROP INDEX IDX_95D5125219EB6921 ON sales_document');
        $this->addSql('ALTER TABLE sales_document DROP client_id');
    }
}
