<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove skill relation from prestation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestation DROP FOREIGN KEY FK_5B7E3D2C5585C142');
        $this->addSql('DROP INDEX IDX_5B7E3D2C5585C142 ON prestation');
        $this->addSql('ALTER TABLE prestation DROP skill_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE prestation ADD skill_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE prestation ADD CONSTRAINT FK_5B7E3D2C5585C142 FOREIGN KEY (skill_id) REFERENCES skill (id)');
        $this->addSql('CREATE INDEX IDX_5B7E3D2C5585C142 ON prestation (skill_id)');
    }
}
