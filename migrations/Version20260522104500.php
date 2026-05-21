<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add gcash_number and card_type columns to order table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('order')) {
            return;
        }

        $table = $schema->getTable('order');

        if (!$table->hasColumn('gcash_number')) {
            $this->addSql('ALTER TABLE `order` ADD gcash_number VARCHAR(20) DEFAULT NULL');
        }

        if (!$table->hasColumn('card_type')) {
            $this->addSql('ALTER TABLE `order` ADD card_type VARCHAR(30) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('order')) {
            return;
        }

        $table = $schema->getTable('order');

        if ($table->hasColumn('gcash_number')) {
            $this->addSql('ALTER TABLE `order` DROP gcash_number');
        }

        if ($table->hasColumn('card_type')) {
            $this->addSql('ALTER TABLE `order` DROP card_type');
        }
    }
}
