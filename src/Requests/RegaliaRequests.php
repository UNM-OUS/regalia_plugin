<?php

namespace DigraphCMS_Plugins\unmous\regalia\Requests;

use DigraphCMS\DB\DB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;

class RegaliaRequests
{
    public static function get(?int $id): ?RegaliaRequest
    {
        static $cache = [];
        if ($id === null) return null;
        return @$cache[$id]
            ?? static::select()->where('regalia_request.id', $id)->fetch();
    }

    public static function select(): RegaliaRequestSelect
    {
        return new RegaliaRequestSelect(
            DB::query()->from('regalia_request')
        );
    }

    public static function semester(Semester $semester): RegaliaRequestSelect
    {
        return new RegaliaRequestSelect(
            DB::query()->from('regalia_request')
                ->where('regalia_request.semester', $semester->intVal())
        );
    }
}
