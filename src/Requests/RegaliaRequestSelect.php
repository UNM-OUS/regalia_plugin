<?php

namespace DigraphCMS_Plugins\unmous\regalia\Requests;

use DigraphCMS\DB\AbstractMappedSelect;

/**
 * @method RegaliaRequest|null fetch()
 * @method RegaliaRequest[] fetchAll()
 */
class RegaliaRequestSelect extends AbstractMappedSelect
{
    protected $returnObjectClass = RegaliaRequest::class;

    /**
     * @return static
     */
    public function cancelled()
    {
        $this->where('regalia_request.cancelled = 1');
        return $this;
    }

    /**
     * @return static
     */
    public function nonCancelled()
    {
        $this->where('regalia_request.cancelled = 0');
        return $this;
    }

    /**
     * @return static
     */
    public function unassigned()
    {
        $this->where('regalia_request.assigned_order IS NULL');
        return $this;
    }

    /**
     * @return static
     */
    public function assigned()
    {
        $this->where('regalia_request.assigned_order IS NOT NULL');
        return $this;
    }
}
