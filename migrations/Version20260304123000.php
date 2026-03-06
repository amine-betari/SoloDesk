<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260304123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add skills table and collaborator_skill relation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE skill (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, name VARCHAR(120) NOT NULL, INDEX IDX_D531167079D1C50F (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collaborator_skill (collaborator_id INT NOT NULL, skill_id INT NOT NULL, INDEX IDX_6C8BA8C79CB99B5 (collaborator_id), INDEX IDX_6C8BA8C5585C142 (skill_id), PRIMARY KEY(collaborator_id, skill_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_D531167079D1C50F FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE collaborator_skill ADD CONSTRAINT FK_6C8BA8C79CB99B5 FOREIGN KEY (collaborator_id) REFERENCES collaborator (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collaborator_skill ADD CONSTRAINT FK_6C8BA8C5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collaborator DROP skills');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE collaborator_skill DROP FOREIGN KEY FK_6C8BA8C79CB99B5');
        $this->addSql('ALTER TABLE collaborator_skill DROP FOREIGN KEY FK_6C8BA8C5585C142');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_D531167079D1C50F');
        $this->addSql('DROP TABLE collaborator_skill');
        $this->addSql('DROP TABLE skill');
        $this->addSql('ALTER TABLE collaborator ADD skills LONGTEXT DEFAULT NULL');
    }
}
