<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240925114516 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_newsletter (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, contact_email VARCHAR(255) NOT NULL, INDEX IDX_B93CDC5FE7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE contact_newsletter_newsletter_type (contact_newsletter_id INT NOT NULL, newsletter_type_id INT NOT NULL, INDEX IDX_9B7DADA43435A90 (contact_newsletter_id), INDEX IDX_9B7DADA4CAF62FFC (newsletter_type_id), PRIMARY KEY(contact_newsletter_id, newsletter_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_newsletter (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, structure_email VARCHAR(255) NOT NULL, INDEX IDX_4CCBB1862534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE structure_newsletter_newsletter_type (structure_newsletter_id INT NOT NULL, newsletter_type_id INT NOT NULL, INDEX IDX_67F2ADE27291268F (structure_newsletter_id), INDEX IDX_67F2ADE2CAF62FFC (newsletter_type_id), PRIMARY KEY(structure_newsletter_id, newsletter_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE contact_newsletter ADD CONSTRAINT FK_B93CDC5FE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE contact_newsletter_newsletter_type ADD CONSTRAINT FK_9B7DADA43435A90 FOREIGN KEY (contact_newsletter_id) REFERENCES contact_newsletter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_newsletter_newsletter_type ADD CONSTRAINT FK_9B7DADA4CAF62FFC FOREIGN KEY (newsletter_type_id) REFERENCES newsletter_type (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_newsletter ADD CONSTRAINT FK_4CCBB1862534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE structure_newsletter_newsletter_type ADD CONSTRAINT FK_67F2ADE27291268F FOREIGN KEY (structure_newsletter_id) REFERENCES structure_newsletter (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure_newsletter_newsletter_type ADD CONSTRAINT FK_67F2ADE2CAF62FFC FOREIGN KEY (newsletter_type_id) REFERENCES newsletter_type (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_newsletter DROP FOREIGN KEY FK_B93CDC5FE7A1254A');
        $this->addSql('ALTER TABLE contact_newsletter_newsletter_type DROP FOREIGN KEY FK_9B7DADA43435A90');
        $this->addSql('ALTER TABLE contact_newsletter_newsletter_type DROP FOREIGN KEY FK_9B7DADA4CAF62FFC');
        $this->addSql('ALTER TABLE structure_newsletter DROP FOREIGN KEY FK_4CCBB1862534008B');
        $this->addSql('ALTER TABLE structure_newsletter_newsletter_type DROP FOREIGN KEY FK_67F2ADE27291268F');
        $this->addSql('ALTER TABLE structure_newsletter_newsletter_type DROP FOREIGN KEY FK_67F2ADE2CAF62FFC');
        $this->addSql('DROP TABLE contact_newsletter');
        $this->addSql('DROP TABLE contact_newsletter_newsletter_type');
        $this->addSql('DROP TABLE structure_newsletter');
        $this->addSql('DROP TABLE structure_newsletter_newsletter_type');
    }
}
