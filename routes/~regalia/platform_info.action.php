<?php

use DigraphCMS\UI\Format;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedList;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;
use DigraphCMS_Plugins\unmous\convocations\AbstractRSVP;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequest;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequester;

$semester = Semesters::current();

printf("<h1>%s platform regalia information</h1>", $semester);

$regalia = RegaliaOrders::select()
    ->semester($semester)
    ->type('platform')
    ->where('identifier is not null')
    ->order('last_name asc, first_name asc');

$table = new PaginatedTable(
    $regalia,
    function (RegaliaOrder $order) use ($semester): array {
        $requests = (new RegaliaRequester($semester, $order->identifier()))->requests();
        $requests = array_filter($requests, function (RegaliaRequest $request): bool {
            return !($request->parent() instanceof RSVP);
        });
        $requests = array_map(function (RegaliaRequest $request): string {
            $parent = $request->parent();
            if ($parent instanceof AbstractRSVP) {
                return sprintf(
                    '%s<br><a href="%s">%s</a>',
                    Format::date($parent->convocation()->time()),
                    $parent->url(),
                    $parent->convocation()->name()
                );
            }
            return sprintf('<a href="%s">%s</a>', $parent->url(), $parent->name());
        }, $requests);
        return [
            sprintf('<a href="%s">%s #%s</a>', $order->url(), $order->type(), $order->id()),
            $order->lastName(),
            $order->firstName(),
            $requests ? new PaginatedList($requests, null) : '',
        ];
    },
    [
        'Order',
        new ColumnStringFilteringHeader('Last name', 'last_name'),
        new ColumnStringFilteringHeader('First name', 'first_Name'),
        'Non-Commencement requests',
    ]
);
$table->paginator()->perPage(100);

echo $table;
