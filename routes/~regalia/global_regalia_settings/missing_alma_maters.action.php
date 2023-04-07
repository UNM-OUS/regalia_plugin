<?php

use DigraphCMS\URL\URL;
?>
<h1>Missing alma maters</h1>
<p>
    This page lists all missing alma maters currently in the system.
    Each should include a name and colors entered by the requester, and you will need to either find it for them or
    <a href="<?= new URL('institutions/') ?>" target="_blank">add an alias on the institutions list</a>.
</p>
<?php

use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Forms\InstitutionInput;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

$requests = Regalia::people()
    ->where('institution_notfound', '1')
    ->order('id asc');

$table = new PaginatedTable(
    $requests,
    function (array $row): array {
        // set up form
        $form = new FormWrapper('row_' . $row['id']);
        $form->addClass('inline-autoform');
        $form->button()->setText('Save');
        $form->setData('target', '_frame');
        $input = (new InstitutionInput('row_input_' . $row['id']))
            ->setRequired(true);
        $form->addChild($input);
        // form callback to save person's alma mater
        $form->addCallback(function () use ($row, $input) {
            Regalia::query()
                ->update('regalia_person', [
                    'institution_id' => $input->value(),
                    'institution_notfound' => 0
                ])
                ->where('identifier', $row['identifier'])
                ->execute();
            throw new RefreshException();
        });
        // return row
        return [
            $row['identifier'],
            PersonInfo::getFullNameFor($row['identifier']),
            PersonInfo::getFor($row['identifier'], 'institution_notfound'),
            $form
        ];
    }
);

echo $table;
