<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class NullRegaliaColumns extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_order')
            ->changeColumn('size_gender', 'string', ['length' => 1, 'null' => true])
            ->changeColumn('size_height', 'integer', ['null' => true])
            ->changeColumn('size_weight', 'integer', ['null' => true])
            ->changeColumn('size_hat', 'string', ['length' => 2, 'null' => true])
            ->changeColumn('degree_level', 'string', ['length' => 10, 'null' => true])
            ->changeColumn('degree_field', 'string', ['length' => 50, 'null' => true])
            ->changeColumn('inst_name', 'string', ['length' => 150, 'null' => true])
            ->changeColumn('inst_city', 'string', ['length' => 100, 'null' => true])
            ->changeColumn('color_band', 'string', ['length' => 25, 'null' => true])
            ->save();
    }
}
