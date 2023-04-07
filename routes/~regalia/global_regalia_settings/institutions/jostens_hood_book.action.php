<h1>Jostens hood book</h1>
<p>
    This is the raw data used for preparing orders in the format required by Jostens.
</p>
<?php

use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;

$query = SharedDB::query()->from('jostens_institution')
    ->where('institution_deprecated <> ?', [true])
    ->order('institution_name ASC');

$table = new PaginatedTable(
    $query,
    function (array $row): array {
        return [
            $row['institution_name'],
            $row['institution_city'],
            $row['institution_state'],
            $row['institution_color_lining1'],
            $row['institution_color_lining2'],
            $row['institution_color_chevron1'],
            $row['institution_color_chevron2'],
            $row['institution_color_chevron3']
        ];
    },
    [
        'School Name',
        'City',
        'St',
        'Lining 1',
        'Lining 2',
        'Chevron 1',
        'Chevron 2',
        'Chevron 3',
    ]
);

$table->download(
    'Jostens hood book export',
    function (array $row): array {
        return [
            $row['institution_name'],
            $row['institution_city'],
            $row['institution_state'],
            $row['institution_color_lining1'],
            $row['institution_color_lining2'],
            $row['institution_color_chevron1'],
            $row['institution_color_chevron2'],
            $row['institution_color_chevron3']
        ];
    },
    [
        'School Name',
        'City',
        'State',
        'Lining 1',
        'Lining 2',
        'Chevron 1',
        'Chevron 2',
        'Chevron 3',
    ]
);

echo $table;
