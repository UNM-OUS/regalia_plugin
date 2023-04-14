<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * This migration is being added to coincide with updating to Phinx 0.13, to
 * help make sure all the index columns line up with the new default types.
 */
final class PhinxRegaliaCleanup extends AbstractMigration
{
    public function change(): void
    {
        // drop all foreign keys to primary key id columns
        $foreign_keys = [
            'regalia_order' => ['column' => 'group_id', 'table' => 'regalia_group'],
            'regalia_request' => ['column' => 'assigned_order', 'table' => 'regalia_order'],
        ];
        // hacky solution for also doing regalia_billing
        if ($this->hasTable('regalia_billing')) {
            $foreign_keys['regalia_billing'] = ['column' => 'order_id', 'table' => 'regalia_order'];
        }
        foreach ($foreign_keys as $table => $key) {
            $this->table($table)
                ->dropForeignKey($key['column'])
                ->save();
        }
        // update all the primary key id columns
        $primary_keys = [
            'regalia_group', 'regalia_order', 'regalia_request'
        ];
        // hacky solution for also doing regalia_billing
        if ($this->hasTable('regalia_billing')) {
            $primary_keys[] = 'regalia_billing';
        }
        foreach ($primary_keys as $table) {
            $this->table($table)
                ->changeColumn('id', 'integer', ['signed' => false, 'null' => false, 'identity' => true])
                ->changePrimaryKey('id')
                ->save();
        }
        // re-add all the foreign keys that reference primary key id columns
        foreach ($foreign_keys as $table => $key) {
            $this->table($table)
                ->changeColumn($key['column'], 'integer', ['null' => false, 'signed' => false])
                ->addForeignKey($key['column'], $key['table'])
                ->save();
        }
    }
}
