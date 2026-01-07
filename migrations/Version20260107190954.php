<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107190954 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attachments (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, path VARCHAR(500) NOT NULL, size INTEGER NOT NULL, created_at DATETIME NOT NULL, card_id INTEGER NOT NULL, CONSTRAINT FK_47C4FAD64ACC9A20 FOREIGN KEY (card_id) REFERENCES cards (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_47C4FAD64ACC9A20 ON attachments (card_id)');
        $this->addSql('CREATE TABLE board_lists (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, position INTEGER NOT NULL, created_at DATETIME NOT NULL, board_id INTEGER NOT NULL, CONSTRAINT FK_AA9B788EE7EC5785 FOREIGN KEY (board_id) REFERENCES boards (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_AA9B788EE7EC5785 ON board_lists (board_id)');
        $this->addSql('CREATE TABLE boards (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, position INTEGER NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_F3EE4D13A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F3EE4D13A76ED395 ON boards (user_id)');
        $this->addSql('CREATE TABLE cards (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, position INTEGER NOT NULL, created_at DATETIME NOT NULL, list_id INTEGER NOT NULL, CONSTRAINT FK_4C258FD3DAE168B FOREIGN KEY (list_id) REFERENCES board_lists (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4C258FD3DAE168B ON cards (list_id)');
        $this->addSql('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE attachments');
        $this->addSql('DROP TABLE board_lists');
        $this->addSql('DROP TABLE boards');
        $this->addSql('DROP TABLE cards');
        $this->addSql('DROP TABLE users');
    }
}
