<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class OrderCreationDates extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_order')
            ->addColumn('created', 'bigint', ['signed' => false, 'default' => time()])
            ->save();
        $this->table('regalia_order')
            ->changeColumn('created', 'bigint', ['signed' => false])
            ->save();
    }
}
