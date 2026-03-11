<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260310120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add prestation table linked to collaborators, skills, and sales documents';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE prestation (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, collaborator_id INT NOT NULL, skill_id INT DEFAULT NULL, sales_document_id INT DEFAULT NULL, label VARCHAR(255) NOT NULL, quantity NUMERIC(10, 3) DEFAULT NULL, unit_price NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(20) NOT NULL, performed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', notes LONGTEXT DEFAULT NULL, INDEX IDX_5B7E3D2C79D1C50F (company_id), INDEX IDX_5B7E3D2C9CB99B5 (collaborator_id), INDEX IDX_5B7E3D2C5585C142 (skill_id), INDEX IDX_5B7E3D2C873469F3 (sales_document_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE prestation ADD CONSTRAINT FK_5B7E3D2C79D1C50F FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE prestation ADD CONSTRAINT FK_5B7E3D2C9CB99B5 FOREIGN KEY (collaborator_id) REFERENCES collaborator (id)');
        $this->addSql('ALTER TABLE prestation ADD CONSTRAINT FK_5B7E3D2C5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id)');
        $this->addSql('ALTER TABLE prestation ADD CONSTRAINT FK_5B7E3D2C873469F3 FOREIGN KEY (sales_document_id) REFERENCES sales_document (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestation DROP FOREIGN KEY FK_5B7E3D2C79D1C50F');
        $this->addSql('ALTER TABLE prestation DROP FOREIGN KEY FK_5B7E3D2C9CB99B5');
        $this->addSql('ALTER TABLE prestation DROP FOREIGN KEY FK_5B7E3D2C5585C142');
        $this->addSql('ALTER TABLE prestation DROP FOREIGN KEY FK_5B7E3D2C873469F3');
        $this->addSql('DROP TABLE prestation');
    }
}
