<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Regalia;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroups;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequest;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequester;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequesters;

$group = RegaliaGroups::get(intval(Context::url()->actionSuffix()));
if (!$group) throw new HttpError(404);
Breadcrumb::setTopName('Assign extras');
Breadcrumb::parent($group->url());

echo "<h1>Assign extras: " . $group->name() . "</h1>";
echo "<p>This interface displays requests that are not already assigned an extra, and allows you to assign them an extra within this group.</p>";

// basic request query
$requests = (RegaliaRequesters::semester($group->semester()))
    ->nonCancelled()
    ->unassigned()
    ->order(null);

// sort requests with the same requested type as this group to the top
if ($group->type() == 'normal') {
    $requests->order('CASE WHEN regalia_request.preferred_group IS NULL THEN 1 ELSE 2 END ASC');
} else {
    $requests->order('CASE WHEN preferred_group = ' . DB::pdo()->quote($group->type()) . ' THEN 1 ELSE 2 END ASC');
}

// order unassigned and oldest requests first
$requests
    ->order('CASE WHEN regalia_request.assigned_order IS NULL THEN 1 ELSE 2 END ASC')
    ->order('regalia_request.id ASC');

// left join regalia order group into query and filter out requests that aren't already here
$requests->leftJoin('regalia_group on regalia_group.id = regalia_order.group_id')
    ->where('(regalia_group.id IS NULL OR regalia_group.id <> ?)', [$group->id()]);

// set up form
$form = new FormWrapper('requests');
$form->setDisplayChildren(false);
$form->button()->setText('Save changes');

// set up table
$listedExtras = [];
$table = new PaginatedTable(
    $requests,
    function (RegaliaRequester $requester) use ($form, $group, &$listedExtras): array {
        // create select field for taking an action on this person
        $options = ['nothing' => '-- do nothing --'];
        $default = null;
        // offer option to reuse an existing order linked to this person
        foreach ($requester->requests() as $request) {
            if ($order = $request->order()) {
                $options['order_' . $order->id()] = 'Use ' . $order->type() . ' #' . $order->id();
                $default = 'order_' . $order->id();
            }
        }
        // look for other existing orders linked to this person ID
        $query = RegaliaOrders::select()
            ->where('semester', $group->semester()->intVal())
            ->where('identifier', $requester->identifier());
        while ($order = $query->fetch()) {
            $options['order_' . $order->id()] = 'Use ' . $order->type() . ' #' . $order->id();
        }
        // look for extras that would work well for this person
        $person = Regalia::getFullPersonInfo($requester->identifier());
        $extras = $group->extras()
            ->group('regalia_order.size_height')
            ->order(null)
            ->order('regalia_order.size_height DESC')
            ->where('regalia_order.size_height < ?', [$person['size_height']])
            ->where('regalia_order.size_height >= ?', [$person['size_height'] - 6]);
        if ($listedExtras) $extras->where('regalia_order.id NOT', $listedExtras);
        while ($extra = $extras->fetch()) {
            $options['extra_' . $extra->id()] = 'Use extra #' . $extra->id() . ': ' . $extra->heightHR();
            $listedExtras[] = $extra->id();
            $default = $default ?? 'extra_' . $extra->id();
        }
        // TODO: look for less optimal matches, like orders that desire cancellation
        // set up select field
        $select = (new SELECT($options))
            ->setID(md5($requester->identifier()))
            ->setDefault($default);
        $form->addChild($select);
        // event listener
        $form->addCallback(function () use ($select, $requester) {
            // assign the given order to this requester
            if (substr($select->value(), 0, 6) == 'order_') {
                if ($order = RegaliaOrders::get(intval(substr($select->value(), 6)))) {
                    $requester->assignOrder($order);
                }
            }
            // assign the given order to this requester
            elseif (substr($select->value(), 0, 6) == 'extra_') {
                if ($order = RegaliaOrders::get(intval(substr($select->value(), 6)))) {
                    $requester->assignExtra($order);
                }
            }
        });
        // return array for table
        return [
            PersonInfo::getFullNameFor($requester->identifier()) ?? $requester->identifier(),
            sprintf(
                '%s\' %s"',
                floor($person['size_height'] / 12),
                $person['size_height'] % 12
            ),
            new PaginatedTable(
                $requester->requests(),
                function (RegaliaRequest $request) {
                    return [
                        sprintf('<a href="%s" target="_blank">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType()),
                        $request->order() ? sprintf('%s - %s #%s', $request->order()->group()->name(), $request->order()->type(), $request->order()->id()) : '',
                    ];
                }
            ),
            new PaginatedTable(
                RegaliaOrders::select()
                    ->where('semester', $group->semester()->intVal())
                    ->where('identifier', $requester->identifier()),
                function (RegaliaOrder $order): array {
                    return [
                        sprintf('<a href="%s" target="_blank">%s</a>', $order->group()->url(), $order->group()->name()),
                        $order->type() . ' #' . $order->id()
                    ];
                }
            ),
            $select
        ];
    },
    [
        'Requester',
        'Height',
        'Requests',
        'Orders',
        'Action'
    ]
);
$table->paginator()->perPage(1000);
echo $table;

// print form and set up refresh callback
$form->addCallback(function () {
    throw new RefreshException();
});
if ($table->source()->count()) echo $form;
