<h1>Past requests</h1>
<?php

use DigraphCMS\Datastore\DatastoreItem;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\regalia\Regalia;
use DigraphCMS_Plugins\unmous\regalia\RegaliaInfoRequests;

echo new PaginatedTable(
    RegaliaInfoRequests::datastore()
        ->select()
        ->order('created desc'),
    function (DatastoreItem $item): array {
        return [
            $item->value(),
            Format::date($item->created()),
            $item->createdBy(),
            Regalia::validatePersonInfo($item->value()) ? 'ENTERED' : ''
        ];
    },
    [
        'Identifier',
        'Created',
        'Created by',
        'Status'
    ]
);
