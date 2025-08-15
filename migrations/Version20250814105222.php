<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250814105222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD sales_document_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D873469F3 FOREIGN KEY (sales_document_id) REFERENCES sales_document (id)');
        $this->addSql('CREATE INDEX IDX_6D28840D873469F3 ON payment (sales_document_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D873469F3');
        $this->addSql('DROP INDEX IDX_6D28840D873469F3 ON payment');
        $this->addSql('ALTER TABLE payment DROP sales_document_id');
    }
}
