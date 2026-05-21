<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260308043718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('order_item')) {
            $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F093DA206A5');
            $this->addSql('DROP TABLE order_item');
        }

        if ($schema->hasTable('cafe_users')) {
            $this->addSql('DROP TABLE cafe_users');
        }

        if ($schema->hasTable('product')) {
            $productTable = $schema->getTable('product');

            if ($productTable->hasColumn('type') && !$productTable->hasColumn('category')) {
                $this->addSql('ALTER TABLE product CHANGE type category VARCHAR(255) NOT NULL');
            }
        }

        if ($schema->hasTable('order')) {
            $orderTable = $schema->getTable('order');

            if ($orderTable->hasColumn('order_date')) {
                $this->addSql('ALTER TABLE `order` ADD order_number VARCHAR(50) DEFAULT NULL, ADD total DOUBLE PRECISION DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
                $this->addSql("UPDATE `order` SET order_number = COALESCE(order_number, CONCAT('ORD-', id)), total = COALESCE(total, total_amount), created_at = COALESCE(created_at, NOW())");
                $this->addSql('ALTER TABLE `order` CHANGE order_number order_number VARCHAR(50) NOT NULL, CHANGE total total DOUBLE PRECISION NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP order_date, DROP total_amount, DROP payment_method');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cafe_users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, roles JSON NOT NULL, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, first_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, last_name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_login_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, order_entity_id INT NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 2) NOT NULL, INDEX IDX_52EA1F093DA206A5 (order_entity_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F093DA206A5 FOREIGN KEY (order_entity_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F647A76ED395');
        $this->addSql('ALTER TABLE stock DROP FOREIGN KEY FK_4B3656604584665A');
        $this->addSql('DROP TABLE activity_log');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE stock');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3');
        $this->addSql('ALTER TABLE `order` ADD order_date VARCHAR(255) NOT NULL, ADD total_amount NUMERIC(10, 2) NOT NULL, ADD payment_method VARCHAR(50) DEFAULT NULL, DROP order_number, DROP total, DROP created_at, CHANGE status status VARCHAR(50) NOT NULL');
    }
}
