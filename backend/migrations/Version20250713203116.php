<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250713203116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id UUID NOT NULL, author_id UUID NOT NULL, group_id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, start_time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, start_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E00CEDDEF675F31B ON booking (author_id)');
        $this->addSql('CREATE INDEX IDX_E00CEDDEFE54D947 ON booking (group_id)');
        $this->addSql('COMMENT ON COLUMN booking.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN booking.author_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN booking.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN booking.end_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN booking.start_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE booking_resource (booking_id UUID NOT NULL, resource_id UUID NOT NULL, PRIMARY KEY(booking_id, resource_id))');
        $this->addSql('CREATE INDEX IDX_87A56A9B3301C60 ON booking_resource (booking_id)');
        $this->addSql('CREATE INDEX IDX_87A56A9B89329D25 ON booking_resource (resource_id)');
        $this->addSql('COMMENT ON COLUMN booking_resource.booking_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN booking_resource.resource_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE booking_user (booking_id UUID NOT NULL, user_id UUID NOT NULL, PRIMARY KEY(booking_id, user_id))');
        $this->addSql('CREATE INDEX IDX_9502F4073301C60 ON booking_user (booking_id)');
        $this->addSql('CREATE INDEX IDX_9502F407A76ED395 ON booking_user (user_id)');
        $this->addSql('COMMENT ON COLUMN booking_user.booking_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN booking_user.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE "group" (id UUID NOT NULL, owner_id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, settings JSON DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6DC044C57E3C61F9 ON "group" (owner_id)');
        $this->addSql('COMMENT ON COLUMN "group".id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN "group".owner_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE group_item (id UUID NOT NULL, group_id UUID NOT NULL, name VARCHAR(255) NOT NULL, quantity INT NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_36417E6EFE54D947 ON group_item (group_id)');
        $this->addSql('COMMENT ON COLUMN group_item.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN group_item.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE group_participant (id UUID NOT NULL, group_id UUID NOT NULL, user_id UUID NOT NULL, joined_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, banned_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F22774D0FE54D947 ON group_participant (group_id)');
        $this->addSql('CREATE INDEX IDX_F22774D0A76ED395 ON group_participant (user_id)');
        $this->addSql('COMMENT ON COLUMN group_participant.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN group_participant.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN group_participant.user_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN group_participant.joined_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN group_participant.banned_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE invitation (id UUID NOT NULL, invitee_id UUID NOT NULL, group_id UUID NOT NULL, invited_email VARCHAR(255) NOT NULL, token VARCHAR(255) DEFAULT NULL, status VARCHAR(255) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F11D61A27A512022 ON invitation (invitee_id)');
        $this->addSql('CREATE INDEX IDX_F11D61A2FE54D947 ON invitation (group_id)');
        $this->addSql('COMMENT ON COLUMN invitation.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invitation.invitee_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invitation.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN invitation.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE resource (id UUID NOT NULL, group_id UUID NOT NULL, name VARCHAR(255) NOT NULL, quantity INT NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BC91F416FE54D947 ON resource (group_id)');
        $this->addSql('COMMENT ON COLUMN resource.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN resource.group_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE "user" (id UUID NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN "user".id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDEFE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking_resource ADD CONSTRAINT FK_87A56A9B3301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking_resource ADD CONSTRAINT FK_87A56A9B89329D25 FOREIGN KEY (resource_id) REFERENCES resource (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking_user ADD CONSTRAINT FK_9502F4073301C60 FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE booking_user ADD CONSTRAINT FK_9502F407A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "group" ADD CONSTRAINT FK_6DC044C57E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_item ADD CONSTRAINT FK_36417E6EFE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_participant ADD CONSTRAINT FK_F22774D0FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE group_participant ADD CONSTRAINT FK_F22774D0A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A27A512022 FOREIGN KEY (invitee_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416FE54D947 FOREIGN KEY (group_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDEF675F31B');
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDEFE54D947');
        $this->addSql('ALTER TABLE booking_resource DROP CONSTRAINT FK_87A56A9B3301C60');
        $this->addSql('ALTER TABLE booking_resource DROP CONSTRAINT FK_87A56A9B89329D25');
        $this->addSql('ALTER TABLE booking_user DROP CONSTRAINT FK_9502F4073301C60');
        $this->addSql('ALTER TABLE booking_user DROP CONSTRAINT FK_9502F407A76ED395');
        $this->addSql('ALTER TABLE "group" DROP CONSTRAINT FK_6DC044C57E3C61F9');
        $this->addSql('ALTER TABLE group_item DROP CONSTRAINT FK_36417E6EFE54D947');
        $this->addSql('ALTER TABLE group_participant DROP CONSTRAINT FK_F22774D0FE54D947');
        $this->addSql('ALTER TABLE group_participant DROP CONSTRAINT FK_F22774D0A76ED395');
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A27A512022');
        $this->addSql('ALTER TABLE invitation DROP CONSTRAINT FK_F11D61A2FE54D947');
        $this->addSql('ALTER TABLE resource DROP CONSTRAINT FK_BC91F416FE54D947');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE booking_resource');
        $this->addSql('DROP TABLE booking_user');
        $this->addSql('DROP TABLE "group"');
        $this->addSql('DROP TABLE group_item');
        $this->addSql('DROP TABLE group_participant');
        $this->addSql('DROP TABLE invitation');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE "user"');
    }
}
