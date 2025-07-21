<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250721194540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE group_item DROP CONSTRAINT fk_36417e6efe54d947');
        $this->addSql('DROP TABLE group_item');
        $this->addSql('ALTER TABLE invitation ADD accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE invitation ADD declined_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN invitation.accepted_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN invitation.declined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE resource ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE resource ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN resource.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN resource.updated_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE group_item (id UUID NOT NULL, group_id UUID NOT NULL, name VARCHAR(255) NOT NULL, quantity INT NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_36417e6efe54d947 ON group_item (group_id)');
        $this->addSql('COMMENT ON COLUMN group_item.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN group_item.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE group_item ADD CONSTRAINT fk_36417e6efe54d947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resource DROP created_at');
        $this->addSql('ALTER TABLE resource DROP updated_at');
        $this->addSql('ALTER TABLE invitation DROP accepted_at');
        $this->addSql('ALTER TABLE invitation DROP declined_at');
    }
}
