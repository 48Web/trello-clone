<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
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
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform),
            'This migration is intended for MySQL.'
        );

        $this->addSql('CREATE TABLE users (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE boards (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            position INT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_F3EE4D13A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE boards ADD CONSTRAINT FK_F3EE4D13A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');

        $this->addSql('CREATE TABLE board_lists (
            id INT AUTO_INCREMENT NOT NULL,
            board_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            position INT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_AA9B788EE7EC5785 (board_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE board_lists ADD CONSTRAINT FK_AA9B788EE7EC5785 FOREIGN KEY (board_id) REFERENCES boards (id)');

        $this->addSql('CREATE TABLE cards (
            id INT AUTO_INCREMENT NOT NULL,
            list_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            position INT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_4C258FD3DAE168B (list_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cards ADD CONSTRAINT FK_4C258FD3DAE168B FOREIGN KEY (list_id) REFERENCES board_lists (id)');

        $this->addSql('CREATE TABLE attachments (
            id INT AUTO_INCREMENT NOT NULL,
            card_id INT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            path VARCHAR(500) NOT NULL,
            size INT NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_47C4FAD64ACC9A20 (card_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE attachments ADD CONSTRAINT FK_47C4FAD64ACC9A20 FOREIGN KEY (card_id) REFERENCES cards (id)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform),
            'This migration is intended for MySQL.'
        );

        // Drop children first to avoid FK constraint errors.
        $this->addSql('DROP TABLE attachments');
        $this->addSql('DROP TABLE cards');
        $this->addSql('DROP TABLE board_lists');
        $this->addSql('DROP TABLE boards');
        $this->addSql('DROP TABLE users');
    }
}
