<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OrderCreationDates extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_order')
            ->addColumn('created', 'biginteger', ['signed' => false, 'default' => time(), 'null' => false])
            ->save();
        $this->table('regalia_order')
            ->changeColumn('created', 'biginteger', ['signed' => false, 'null' => false])
            ->save();
    }
}
