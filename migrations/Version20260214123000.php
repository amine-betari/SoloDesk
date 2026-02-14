<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214123000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create company_setting table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE company_setting (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, setting_key VARCHAR(100) NOT NULL, setting_value LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, INDEX IDX_19DC58E0979B1AD6 (company_id), UNIQUE INDEX uniq_company_setting_key (company_id, setting_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE company_setting ADD CONSTRAINT FK_19DC58E0979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE company_setting DROP FOREIGN KEY FK_19DC58E0979B1AD6');
        $this->addSql('DROP TABLE company_setting');
    }
}
