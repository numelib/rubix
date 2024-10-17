<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240926075453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE structure_contact DROP FOREIGN KEY FK_97C096092534008B');
        $this->addSql('ALTER TABLE structure_contact DROP FOREIGN KEY FK_97C09609E7A1254A');
        $this->addSql('DROP TABLE structure_contact');
        $this->addSql('ALTER TABLE contact ADD festival_program_receipt_address VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE structure DROP is_festival_program_sent_to_structure');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE structure_contact (structure_id INT NOT NULL, contact_id INT NOT NULL, INDEX IDX_97C096092534008B (structure_id), INDEX IDX_97C09609E7A1254A (contact_id), PRIMARY KEY(structure_id, contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE structure_contact ADD CONSTRAINT FK_97C096092534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_contact ADD CONSTRAINT FK_97C09609E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure ADD is_festival_program_sent_to_structure TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE contact DROP festival_program_receipt_address');
    }
}
