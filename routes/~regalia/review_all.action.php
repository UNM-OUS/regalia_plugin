<?php

use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedList;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\commencement\SignupWindows\RSVP;
use DigraphCMS_Plugins\unmous\convocations\FacultyRSVP;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequest;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequests;

$semester = Semesters::current();

printf("<h1>Review all %s regalia orders</h1>", $semester);

$regalia = RegaliaOrders::select()
    ->semester($semester)
    ->where('identifier is not null')
    ->order('last_name asc, first_name asc, identifier asc')
    ->fetchAll();

$table = new PaginatedTable(
    $regalia,
    function (RegaliaOrder $order): array {
        $levenThreshold = 3;
        // get name from PersonInfo and prep for display
        $piName = PersonInfo::getFirstNameFor($order->identifier()) . ' ' . PersonInfo::getLastNameFor($order->identifier());
        if (levenshtein(strtolower($piName), strtolower($order->firstName() . ' ' . $order->lastName())) > $levenThreshold) $piName = "<span class='notification notification--warning'>$piName</span>";
        // get names and identifiers from RSVPs and prep for display
        $rsvpNames = [];
        $rsvpIdentifiers = [];
        $requests = RegaliaRequests::select()->where('assigned_order', $order->id());
        /** @var RegaliaRequest $request */
        foreach ($requests as $request) {
            $parent = $request->parent();
            // prepare name entry
            $entry = sprintf('<a href="%s">%s</a>', $parent->url(), $parent->regaliaOrderType());
            $name = '';
            if ($parent instanceof RSVP) $name = $parent->name();
            if ($parent instanceof FacultyRSVP) $name = $parent->name();
            if ($name) {
                if (levenshtein(strtolower($name), strtolower($order->firstName() . ' ' . $order->lastName())) > $levenThreshold) $name = "<span class='notification notification--warning'>$name</span>";
                $entry .= "<br>$name";
            }
            $rsvpNames[] = $entry;
            // prepare identifier entry
            $entry = sprintf('<a href="%s">%s</a>', $parent->url(), $parent->regaliaOrderType());
            $identifiers = [];
            if ($parent instanceof RSVP) $identifiers = [$parent->for()];
            if ($parent instanceof FacultyRSVP) $identifiers = [$parent->identifier()];
            foreach ($identifiers as $identifier) {
                if ($identifier != $order->identifier()) $identifier = "<span class='notification notification--warning'>$identifier</span>";
                $entry .= "<br>$identifier";
            }
            $rsvpIdentifiers[] = $entry;
        }
        // prepare row
        return [
            $order->url()->html(),
            $order->firstName(),
            $order->lastName(),
            $rsvpNames ? new PaginatedList($rsvpNames, null) : '',
            $piName,
            $order->identifier(),
            $rsvpIdentifiers ? new PaginatedList($rsvpIdentifiers, null) : '',
        ];
    },
    [
        'Order',
        new ColumnStringFilteringHeader('Order first name', 'first_name'),
        new ColumnStringFilteringHeader('Order last name', 'last_name'),
        'RSVP names',
        'PersonInfo name',
        new ColumnStringFilteringHeader('Order identifier', 'identifier'),
        'RSVP identifiers'
    ]
);

$table->paginator()->perPage(1000);

echo $table;
