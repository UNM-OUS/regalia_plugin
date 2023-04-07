<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegaliaRequests extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_request')
            ->addColumn('semester', 'integer')
            ->addColumn('identifier', 'string', ['length' => 150])
            ->addColumn('parent', 'uuid')
            ->addColumn('preferred_group', 'string', ['length' => 50, 'null' => true])
            ->addColumn('cancelled', 'boolean')
            ->addColumn('assigned_order', 'integer', ['null' => true])
            ->addColumn('data', 'json')
            ->addForeignKey('assigned_order', 'regalia_order')
            ->addIndex(['semester', 'identifier', 'parent'], ['unique' => true])
            ->addIndex('semester')
            ->addIndex('identifier')
            ->addIndex('parent')
            ->create();
    }
}
