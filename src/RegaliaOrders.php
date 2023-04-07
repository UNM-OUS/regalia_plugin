<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\DB\DB;

class RegaliaOrders
{
    public static function select(): RegaliaOrderSelect
    {
        return new RegaliaOrderSelect(
            DB::query()->from('regalia_order')
                ->leftJoin('regalia_group on regalia_group.id = regalia_order.group_id')
                ->select('regalia_order.*', true)
                ->select('regalia_group.semester as semester')
                ->select('regalia_group.type as group_type')
                ->select('regalia_group.tams as group_tams')
        );
    }

    public static function get(?int $id): ?RegaliaOrder
    {
        static $cache = [];
        if ($id === null) return null;
        return $cache[$id]
            ?? $cache[$id] = static::select()->where('regalia_order.id = ?', [$id])->fetch();
    }
}
