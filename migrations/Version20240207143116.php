<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240207143116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, mail VARCHAR(255) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, creneau_id INT NOT NULL, INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), UNIQUE INDEX UNIQ_6EEAA67D7D0729A9 (creneau_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE commande_produit (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, produit_id INT DEFAULT NULL, commande_id INT NOT NULL, INDEX IDX_DF1E9E87F347EFB (produit_id), INDEX IDX_DF1E9E8782EA2E54 (commande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE creneau (id INT AUTO_INCREMENT NOT NULL, date DATE NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE magasin (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, lieu VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE produit (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prix DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE stock (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, produit_id INT NOT NULL, magasin_id INT NOT NULL, INDEX IDX_4B365660F347EFB (produit_id), INDEX IDX_4B36566020096AE3 (magasin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('CREATE TABLE vendeur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, magasin_id INT DEFAULT NULL, INDEX IDX_7AF4999620096AE3 (magasin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67D7D0729A9 FOREIGN KEY (creneau_id) REFERENCES creneau (id)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E87F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE commande_produit ADD CONSTRAINT FK_DF1E9E8782EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B365660F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE stock ADD CONSTRAINT FK_4B36566020096AE3 FOREIGN KEY (magasin_id) REFERENCES magasin (id)');
        $this->addSql('ALTER TABLE vendeur ADD CONSTRAINT FK_7AF4999620096AE3 FOREIGN KEY (magasin_id) REFERENCES magasin (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67D7D0729A9');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E87F347EFB');
        $this->addSql('ALTER TABLE commande_produit DROP FOREIGN KEY FK_DF1E9E8782EA2E54');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B365660F347EFB');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B36566020096AE3');
        $this->addSql('ALTER TABLE vendeur DROP FOREIGN KEY FK_7AF4999620096AE3');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commande_produit');
        $this->addSql('DROP TABLE creneau');
        $this->addSql('DROP TABLE magasin');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE vendeur');
    }
}
