<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211181222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('cafe_users') && !$schema->hasTable('user')) {
            $this->addSql('RENAME TABLE cafe_users TO `user`');
            $this->addSql('ALTER TABLE `user` DROP first_name, DROP last_name, DROP phone, DROP created_at, CHANGE last_login_at last_login DATETIME DEFAULT NULL, CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');

            return;
        }

        if ($schema->hasTable('user')) {
            $userTable = $schema->getTable('user');

            if ($userTable->hasColumn('first_name')) {
                $this->addSql('ALTER TABLE `user` DROP first_name, DROP last_name');
            }

            if ($userTable->hasColumn('phone')) {
                $this->addSql('ALTER TABLE `user` DROP phone');
            }

            if ($userTable->hasColumn('created_at')) {
                $this->addSql('ALTER TABLE `user` DROP created_at');
            }

            if ($userTable->hasColumn('last_login_at')) {
                $this->addSql('ALTER TABLE `user` CHANGE last_login_at last_login DATETIME DEFAULT NULL');
            } elseif (!$userTable->hasColumn('last_login')) {
                $this->addSql('ALTER TABLE `user` ADD last_login DATETIME DEFAULT NULL');
            }

            $this->addSql('ALTER TABLE `user` CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');

            return;
        }

        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_active TINYINT(1) DEFAULT 1 NOT NULL, last_login DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD phone VARCHAR(20) DEFAULT NULL, ADD last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE `order` DROP total, DROP status, DROP created_at');
    }
}
