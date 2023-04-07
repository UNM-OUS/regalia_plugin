<?php

namespace DigraphCMS_Plugins\unmous\regalia\Requests;

use DigraphCMS\DB\DB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;

class RegaliaRequesters
{
    public static function select(): RegaliaRequesterSelect
    {
        return new RegaliaRequesterSelect(
            DB::query()->from('regalia_request')
                ->leftJoin('regalia_order on regalia_order.id = regalia_request.assigned_order')
                ->groupBy('regalia_request.identifier')
                ->select('regalia_request.*', true)
                ->order('regalia_request.id ASC')
        );
    }

    public static function semester(Semester $semester): RegaliaRequesterSelect
    {
        return static::select()
            ->where('regalia_request.semester', $semester->intVal());
    }
}
