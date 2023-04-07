<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\DB\DB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

class RegaliaGroups
{

    public static function select(): RegaliaGroupSelect
    {
        return new RegaliaGroupSelect(DB::query()->from('regalia_group'));
    }

    /**
     * Returns an array, which is cached. This is because this should always be
     * a small number of results, which may be getting reused frequently on some
     * pages, such as when assigning/moving orders.
     * 
     * @param Semester|null $semester
     * @return RegaliaGroup[]
     */
    public static function getBySemester(Semester $semester = null): array
    {
        $semester = $semester ?? Semesters::current();
        static $cache = [];
        return $cache[$semester->intVal()]
            ?? $cache[$semester->intVal()] = static::select()
            ->where('semester', strval($semester->intVal()))
            ->fetchAll();
    }

    public static function get(?int $id): ?RegaliaGroup
    {
        static $cache = [];
        if ($id === null) return null;
        return $cache[$id]
            ?? $cache[$id] = static::select()->where('id', $id)->fetch();
    }
}
