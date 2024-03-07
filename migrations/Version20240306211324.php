<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240306211324 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE creneau ADD magasin_id INT NOT NULL');
        $this->addSql('ALTER TABLE creneau ADD CONSTRAINT FK_F9668B5F20096AE3 FOREIGN KEY (magasin_id) REFERENCES magasin (id)');
        $this->addSql('CREATE INDEX IDX_F9668B5F20096AE3 ON creneau (magasin_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE creneau DROP FOREIGN KEY FK_F9668B5F20096AE3');
        $this->addSql('DROP INDEX IDX_F9668B5F20096AE3 ON creneau');
        $this->addSql('ALTER TABLE creneau DROP magasin_id');
    }
}
