<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522100500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment_method column to order table';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('order')) {
            return;
        }

        $orderTable = $schema->getTable('order');
        if (!$orderTable->hasColumn('payment_method')) {
            $this->addSql("ALTER TABLE `order` ADD payment_method VARCHAR(30) NOT NULL DEFAULT 'cash'");
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('order')) {
            return;
        }

        $orderTable = $schema->getTable('order');
        if ($orderTable->hasColumn('payment_method')) {
            $this->addSql('ALTER TABLE `order` DROP payment_method');
        }
    }
}
