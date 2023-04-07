<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

/**
 * @method RegaliaOrder|null fetch()
 * @method RegaliaOrder[] fetchAll()
 */
class RegaliaOrderSelect extends AbstractMappedSelect
{
    protected $returnObjectClass = RegaliaOrder::class;

    public function semester(Semester $semester = null): static
    {
        $semester = $semester ?? Semesters::current();
        return $this->where('regalia_group.semester', $semester->intVal());
    }

    /**
     * @param string|array<mixed,string> $type_or_types
     * @return static
     */
    public function type($type_or_types): static
    {
        return $this->where('regalia_group.type', $type_or_types);
    }

    /**
     * @return static
     */
    public function extras()
    {
        return $this->where('regalia_order.type', 'extra');
    }

    /**
     * @return static
     */
    public function nonExtras()
    {
        return $this->where('regalia_order.type <> ?', 'extra');
    }

    /**
     * @return static
     */
    public function cancelled()
    {
        $this->where('regalia_order.cancelled = 1');
        return $this;
    }

    /**
     * @return static
     */
    public function nonCancelled()
    {
        $this->where('regalia_order.cancelled = 0');
        return $this;
    }
}
