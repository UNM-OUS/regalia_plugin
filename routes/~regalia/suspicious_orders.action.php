<?php

use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedList;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\degrees\Degrees;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\ous_digraph_module\UserData;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;

$semester = Semesters::current();

printf("<h1>%s suspicious regalia orders</h1>", $semester);

$regalia = RegaliaOrders::select()
    ->semester($semester)
    ->where('identifier is not null')
    ->where('${data.not_suspicious} is null')
    ->order('id desc')
    ->fetchAll();

$warnings = [];
foreach ($regalia as $order) {
    $w = [];
    $id = $order->identifier();
    if (str_contains($order->identifier(), '@')) {
        // if associated with email address, it's suspicious
        $w[] = 'Associated with email instead of NetID';
    } else {
        $netID = $order->identifier();
        // verify faculty/staff status        
        if (!UserData::netIdIsFaculty($netID) && !UserData::netIdIsStaff($netID)) $w[] = 'Could not verify faculty or staff status';
        // check if they're a student graduating last semester or later
        $degrees = Degrees::select()
            ->where('identifier', $netID)
            ->where('semester >= ?', Semesters::current()->previous()->intVal());
        if ($degrees->count()) $w[] = 'Possible graduate using the wrong form';
    }
    if ($w) $warnings[] = [
        'order' => $order,
        'warnings' => $w
    ];
}

echo new PaginatedTable(
    $warnings,
    function (array $w): array {
        /** @var RegaliaOrder */
        $order = $w['order'];
        return [
            sprintf('<a href="%s">%s #%s</a>', $order->url(), $order->type(), $order->id()),
            $order->lastName(),
            $order->firstName(),
            new PaginatedList($w['warnings'], null),
            (new CallbackLink(function () use ($order) {
                $order->data()['not_suspicious'] = true;
                $order->save();
            }, null, null, '_frame'))
                ->addChild('Mark OK')
                ->addClass('button')
                ->setID(md5('mark_ok_' . $order->id()))
        ];
    },
    [
        'Order',
        new ColumnStringFilteringHeader('Last name', 'last_name'),
        new ColumnStringFilteringHeader('First name', 'first_Name'),
        'Non-Commencement requests',
        ''
    ]
);
