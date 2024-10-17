<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241002091602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_discipline (structure_id INT NOT NULL, discipline_id INT NOT NULL, INDEX IDX_47F0DA712534008B (structure_id), INDEX IDX_47F0DA71A5522701 (discipline_id), PRIMARY KEY(structure_id, discipline_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE structure_discipline ADD CONSTRAINT FK_47F0DA712534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_discipline ADD CONSTRAINT FK_47F0DA71A5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure DROP FOREIGN KEY FK_6F0137EAA5522701');
        $this->addSql('DROP INDEX IDX_6F0137EAA5522701 ON structure');
        $this->addSql('ALTER TABLE structure DROP discipline_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_discipline DROP FOREIGN KEY FK_47F0DA712534008B');
        $this->addSql('ALTER TABLE structure_discipline DROP FOREIGN KEY FK_47F0DA71A5522701');
        $this->addSql('DROP TABLE structure_discipline');
        $this->addSql('ALTER TABLE structure ADD discipline_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure ADD CONSTRAINT FK_6F0137EAA5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id)');
        $this->addSql('CREATE INDEX IDX_6F0137EAA5522701 ON structure (discipline_id)');
    }
}
