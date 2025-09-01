<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table category et la relation category_id sur wish (création des catégories)';
    }

    public function up(Schema $schema): void
    {
        // Crée la table category si elle n'existe pas
        $this->addSql('CREATE TABLE IF NOT EXISTS category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_8C489D6C5E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Insère les 5 catégories initiales (IGNORE pour éviter erreur si elles existent déjà)
        $this->addSql("INSERT IGNORE INTO category (name) VALUES
            ('Travel & Adventure'),
            ('Sport'),
            ('Entertainment'),
            ('Human Relations'),
            ('Others')");

        // Ajoute la colonne category_id sur wish uniquement si elle n'existe pas (via information_schema)
        $this->addSql("SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wish' AND COLUMN_NAME = 'category_id')");
        $this->addSql("SET @sql := IF(@col = 0, 'ALTER TABLE wish ADD COLUMN category_id INT DEFAULT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Remplir les lignes existantes : si des wishes ont NULL ou une valeur non présente dans category, on les mappe vers 'Others'
        // On récupère l'ID de la catégorie 'Others' (ou la première catégorie si absente)
        $this->addSql("SET @other_id := (SELECT id FROM category WHERE name = 'Others' LIMIT 1)");
        $this->addSql("SET @other_id := IFNULL(@other_id, (SELECT id FROM category LIMIT 1))");
        // Met à jour les lignes où category_id est NULL
        $this->addSql("UPDATE wish SET category_id = @other_id WHERE category_id IS NULL");
        // Met à jour les lignes où category_id ne correspond à aucune catégorie existante
        $this->addSql("UPDATE wish SET category_id = @other_id WHERE category_id NOT IN (SELECT id FROM category)");

        // Maintenant rendre la colonne NOT NULL
        $this->addSql("SET @col2 := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wish' AND COLUMN_NAME = 'category_id' AND IS_NULLABLE = 'NO')");
        $this->addSql("SET @sql3 := IF(@col2 = 0, 'ALTER TABLE wish MODIFY COLUMN category_id INT NOT NULL', 'SELECT 1')");
        $this->addSql('PREPARE stmt3 FROM @sql3');
        $this->addSql('EXECUTE stmt3');
        $this->addSql('DEALLOCATE PREPARE stmt3');

        // Ajout conditionnel de la contrainte FK : on prépare la requête ALTER TABLE seulement si la contrainte n'existe pas
        $this->addSql("SET @cnt := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'wish' AND CONSTRAINT_NAME = 'FK_5A3B5E6F12469DE2')");
        $this->addSql("SET @sql4 := IF(@cnt = 0, 'ALTER TABLE wish ADD CONSTRAINT FK_5A3B5E6F12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)', 'SELECT 1')");
        $this->addSql('PREPARE stmt4 FROM @sql4');
        $this->addSql('EXECUTE stmt4');
        $this->addSql('DEALLOCATE PREPARE stmt4');
    }

    public function down(Schema $schema): void
    {
        // Tentative de suppression de la contrainte si elle existe
        $this->addSql("SET @cnt := (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'wish' AND CONSTRAINT_NAME = 'FK_5A3B5E6F12469DE2')");
        $this->addSql("SET @sql := IF(@cnt = 1, 'ALTER TABLE wish DROP FOREIGN KEY FK_5A3B5E6F12469DE2', 'SELECT 1')");
        $this->addSql('PREPARE stmt FROM @sql');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');

        // Supprime la colonne category_id si elle existe
        $this->addSql("SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'wish' AND COLUMN_NAME = 'category_id')");
        $this->addSql("SET @sql2 := IF(@col = 1, 'ALTER TABLE wish DROP COLUMN category_id', 'SELECT 1')");
        $this->addSql('PREPARE stmt2 FROM @sql2');
        $this->addSql('EXECUTE stmt2');
        $this->addSql('DEALLOCATE PREPARE stmt2');

        $this->addSql('DROP TABLE IF EXISTS category');
    }
}
