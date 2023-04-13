<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RegaliaRequests extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_request')
            ->addColumn('semester', 'integer', ['null' => false])
            ->addColumn('identifier', 'string', ['length' => 150, 'null' => false])
            ->addColumn('parent', 'uuid', ['null' => false])
            ->addColumn('preferred_group', 'string', ['length' => 50, 'null' => true])
            ->addColumn('cancelled', 'boolean', ['null' => false])
            ->addColumn('assigned_order', 'integer', ['signed' => false, 'null' => true])
            ->addColumn('data', 'json', ['null' => false])
            ->addForeignKey('assigned_order', 'regalia_order')
            ->addIndex(['semester', 'identifier', 'parent'], ['unique' => true])
            ->addIndex('semester')
            ->addIndex('identifier')
            ->addIndex('parent')
            ->create();
    }
}
