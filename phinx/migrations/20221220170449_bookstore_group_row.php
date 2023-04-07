<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class BookstoreGroupRow extends AbstractMigration
{
    public function change(): void
    {
        $this->table('user_group')
            ->insert([
                'uuid' => 'bookstore',
                'name' => 'Bookstore'
            ])
            ->save();
    }
}
