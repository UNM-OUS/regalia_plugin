<h1>Regalia default orders</h1>

<p>
    Update the saved default regalia orders for people across all OUS sites.
</p>

<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\EmailOrNetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

$form = new FormWrapper();
$form->button()->setText('Add/update person');
$form->addClass('inline-form');

$identifier = (new Field('', new EmailOrNetIDInput()))
    ->setRequired(true);
$form->addChild($identifier);

if ($form->ready()) {
    throw new RedirectException(new URL('person:' . $identifier->value()));
}

echo "<h2>Add/update person by NetID or email</h2>";
echo $form;

echo "<h2>All current data</h2>";
$table = new PaginatedTable(
    Regalia::people(),
    function (array $row): array {
        return [
            sprintf('<a href="%s">%s</a>', new URL('person:' . $row['identifier']), $row['identifier']),
            PersonInfo::getFullNameFor($row['identifier']),
            $row['needs_hat'] == 1 ? 'Hat' : '',
            $row['needs_robe'] == 1 ? 'Robe' : '',
            $row['needs_hood'] == 1 ? 'Hood' : '',
            Regalia::validatePersonInfo($row['identifier'])
                ? '<span class="notification notification--confirmation">Valid</span>'
                : sprintf('<span class="notification notification--error"><a href="%s">Needs update</a></span>', new URL('person:' . $row['identifier'])),
        ];
    },
    [
        new ColumnStringFilteringHeader('Identifier', 'identifier'),
        'Name',
        'Hat',
        'Robe',
        'Hood',
        'Status',
    ]
);

echo $table;
