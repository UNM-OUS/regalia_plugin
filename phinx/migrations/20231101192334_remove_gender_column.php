<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class RemoveGenderColumn extends AbstractMigration
{
    public function change(): void
    {
        $this->table('regalia_order')
            ->removeColumn('size_gender')
            ->save();
    }
}
