<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\DB\AbstractMappedSelect;

/**
 * @method RegaliaGroup|null fetch()
 * @method RegaliaGroup[] fetchAll()
 */
class RegaliaGroupSelect extends AbstractMappedSelect
{
    protected $returnObjectClass = RegaliaGroup::class;
}
