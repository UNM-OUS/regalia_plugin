<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegaliaOrders extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_group')
            ->addColumn('name', 'string', ['length' => 150, 'null' => false])
            ->addColumn('type', 'string', ['length' => 50, 'null' => false])
            ->addColumn('semester', 'integer', ['null' => false])
            ->addColumn('tams', 'boolean', ['null' => false])
            ->addColumn('lock_orders', 'boolean', ['null' => false])
            ->addColumn('lock_cancellation', 'boolean', ['null' => false])
            ->addIndex('semester')
            ->addIndex('type')
            ->create();
        $this->table('regalia_order')
            ->addColumn('group_id', 'integer', ['null' => false])
            ->addColumn('type', 'string', ['length' => 50, 'null' => false])
            ->addColumn('identifier', 'string', ['length' => 150, 'null' => true])
            ->addColumn('email', 'string', ['length' => 250, 'null' => true])
            ->addColumn('last_name', 'string', ['length' => 100, 'null' => true])
            ->addColumn('first_name', 'string', ['length' => 100, 'null' => true])
            ->addColumn('size_gender', 'string', ['length' => 1, 'null' => false])
            ->addColumn('size_height', 'integer', ['null' => false])
            ->addColumn('size_weight', 'integer', ['null' => false])
            ->AddColumn('size_hat', 'string', ['length' => 2, 'null' => false])
            ->addColumn('degree_level', 'string', ['length' => 10, 'null' => false])
            ->addColumn('degree_field', 'string', ['length' => 50, 'null' => false])
            ->addColumn('inst_name', 'string', ['length' => 150, 'null' => false])
            ->addColumn('inst_city', 'string', ['length' => 100, 'null' => false])
            ->addColumn('inst_state', 'string', ['length' => 2, 'null' => true])
            ->addColumn('color_band', 'string', ['length' => 25, 'null' => false])
            ->addColumn('color_lining', 'string', ['length' => 25, 'null' => true])
            ->addColumn('color_chevron', 'string', ['length' => 25, 'null' => true])
            ->addColumn('hat', 'boolean', ['null' => false])
            ->addColumn('tam', 'boolean', ['null' => true])
            ->addColumn('hood', 'boolean', ['null' => false])
            ->addColumn('robe', 'boolean', ['null' => false])
            ->addColumn('cancelled', 'boolean', ['null' => false])
            ->addColumn('data', 'json', ['null' => false])
            ->addForeignKey('group_id', 'regalia_group')
            ->create();
    }
}
