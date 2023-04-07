<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegaliaOrders extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_group')
            ->addColumn('name', 'string', ['length' => 150])
            ->addColumn('type', 'string', ['length' => 50])
            ->addColumn('semester', 'integer')
            ->addColumn('tams', 'boolean')
            ->addColumn('lock_orders', 'boolean')
            ->addColumn('lock_cancellation', 'boolean')
            ->addIndex('semester')
            ->addIndex('type')
            ->create();
        $this->table('regalia_order')
            ->addColumn('group_id', 'integer')
            ->addColumn('type', 'string', ['length' => 50])
            ->addColumn('identifier', 'string', ['length' => 150, 'null' => true])
            ->addColumn('email', 'string', ['length' => 250, 'null' => true])
            ->addColumn('last_name', 'string', ['length' => 100, 'null' => true])
            ->addColumn('first_name', 'string', ['length' => 100, 'null' => true])
            ->addColumn('size_gender', 'string', ['length' => 1])
            ->addColumn('size_height', 'integer')
            ->addColumn('size_weight', 'integer')
            ->AddColumn('size_hat', 'string', ['length' => 2])
            ->addColumn('degree_level', 'string', ['length' => 10])
            ->addColumn('degree_field', 'string', ['length' => 50])
            ->addColumn('inst_name', 'string', ['length' => 150])
            ->addColumn('inst_city', 'string', ['length' => 100])
            ->addColumn('inst_state', 'string', ['length' => 2, 'null' => true])
            ->addColumn('color_band', 'string', ['length' => 25])
            ->addColumn('color_lining', 'string', ['length' => 25, 'null' => true])
            ->addColumn('color_chevron', 'string', ['length' => 25, 'null' => true])
            ->addColumn('hat', 'boolean')
            ->addColumn('tam', 'boolean', ['null' => true])
            ->addColumn('hood', 'boolean')
            ->addColumn('robe', 'boolean')
            ->addColumn('cancelled', 'boolean')
            ->addColumn('data', 'json')
            ->addForeignKey('group_id', 'regalia_group')
            ->create();
    }
}
