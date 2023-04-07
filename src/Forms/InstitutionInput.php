<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteInput;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

class InstitutionInput extends AutocompleteInput
{
    public function __construct(string $id = null)
    {
        parent::__construct(
            $id,
            new URL('/~api/v1/autocomplete/regalia-institution.php'),
            function ($value) {
                $institution = Regalia::institutions()
                    ->where('regalia_institution.id = ?', [$value])
                    ->fetch();
                if (!$institution) return null;
                else return [
                    'html' => sprintf('<div class="label">%s</div>', $institution['label']),
                    'value' => $institution['id']
                ];
            }
        );
    }
}
