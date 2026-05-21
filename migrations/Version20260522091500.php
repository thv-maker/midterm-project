<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260522091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create or align order_item table for itemized customer/admin orders and stock deduction';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('order_item')) {
            $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, product_id INT DEFAULT NULL, quantity INT NOT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_52EA1F098D9F6D38 (order_id), INDEX IDX_52EA1F094584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');

            return;
        }

        $table = $schema->getTable('order_item');

        if ($table->hasColumn('order_entity_id') && !$table->hasColumn('order_id')) {
            $this->addSql('ALTER TABLE order_item CHANGE order_entity_id order_id INT NOT NULL');
        }

        if (!$table->hasColumn('product_id')) {
            $this->addSql('ALTER TABLE order_item ADD product_id INT DEFAULT NULL');
        }

        if (!$table->hasIndex('IDX_52EA1F098D9F6D38')) {
            $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        }

        if (!$table->hasIndex('IDX_52EA1F094584665A')) {
            $this->addSql('CREATE INDEX IDX_52EA1F094584665A ON order_item (product_id)');
        }

        if (!$table->hasForeignKey('FK_52EA1F098D9F6D38')) {
            $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON DELETE CASCADE');
        }

        if (!$table->hasForeignKey('FK_52EA1F094584665A')) {
            $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('order_item')) {
            return;
        }

        $table = $schema->getTable('order_item');

        if ($table->hasForeignKey('FK_52EA1F094584665A')) {
            $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        }

        if ($table->hasForeignKey('FK_52EA1F098D9F6D38')) {
            $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        }

        if ($table->hasColumn('order_id') && !$table->hasColumn('order_entity_id')) {
            $this->addSql('ALTER TABLE order_item CHANGE order_id order_entity_id INT NOT NULL');
        }

        if ($table->hasColumn('product_id')) {
            $this->addSql('ALTER TABLE order_item DROP COLUMN product_id');
        }
    }
}
