<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211171338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('customer')) {
            $customerTable = $schema->getTable('customer');

            if ($customerTable->hasColumn('phone_number')) {
                $this->addSql('ALTER TABLE customer ADD phone VARCHAR(20) DEFAULT NULL, ADD address LONGTEXT DEFAULT NULL');
                $this->addSql('UPDATE customer SET phone = phone_number WHERE phone IS NULL');
                $this->addSql("UPDATE customer SET address = '' WHERE address IS NULL");
                $this->addSql('ALTER TABLE customer CHANGE email email VARCHAR(180) NOT NULL, CHANGE phone phone VARCHAR(20) NOT NULL, CHANGE address address LONGTEXT NOT NULL, DROP phone_number, DROP date_joined, DROP loyalty_points, DROP total_purchases, DROP last_purchase_date, DROP status');
            }
        } else {
            $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, address LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if ($schema->hasTable('order')) {
            $orderTable = $schema->getTable('order');

            if ($orderTable->hasColumn('order_date')) {
                $this->addSql('ALTER TABLE `order` ADD order_number VARCHAR(50) DEFAULT NULL, ADD total DOUBLE PRECISION DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
                $this->addSql("UPDATE `order` SET order_number = COALESCE(order_number, CONCAT('ORD-', id)), total = COALESCE(total, total_amount), created_at = COALESCE(created_at, NOW())");
                $this->addSql('ALTER TABLE `order` CHANGE order_number order_number VARCHAR(50) NOT NULL, CHANGE total total DOUBLE PRECISION NOT NULL, CHANGE status status VARCHAR(20) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', DROP order_date, DROP total_amount, DROP payment_method');
            }
        } else {
            $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, order_number VARCHAR(50) NOT NULL, total DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F52993989395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE `order`');
    }
}
