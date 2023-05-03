<?php

use DigraphCMS\Context;
use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\DB\DB;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Regalia;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroups;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequest;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequester;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequests;

$group = RegaliaGroups::get(intval(Context::url()->actionSuffix()));
if (!$group) throw new HttpError(404);
Breadcrumb::setTopName('Assign requests');
Breadcrumb::parent($group->url());

echo "<h1>Assign regalia: " . $group->name() . "</h1>";
echo "<p>This interface displays requests that are not already assigned to this order group, and provides options to either move or create orders in this group for them.</p>";

if ($group->ordersLocked()) Notifications::printNotice('Modification of orders is now locked so orders cannot be created or moved here. Wherever possible you can and should still use this tool to assign new requests to existing orders though.');

// get a list of all requests
$requesters = (RegaliaRequests::semester($group->semester()))
    ->nonCancelled()
    ->order(null);

// sort requests with the same requested type as this group to the top
if ($group->type() == 'normal') {
    $requesters->order('CASE WHEN regalia_request.preferred_group IS NULL THEN 1 ELSE 2 END ASC');
} else {
    $requesters->order('CASE WHEN regalia_request.preferred_group LIKE ' . DB::pdo()->quote(AbstractMappedSelect::prepareLikePattern($group->type())) . ' THEN 1 ELSE 2 END ASC');
}

// order unassigned and oldest requests first
$requesters
    ->order('CASE WHEN regalia_request.assigned_order IS NULL THEN 1 ELSE 2 END ASC')
    ->order('regalia_request.id ASC');

// filter out requests that are already being fulfilled by this order group
$requesters->leftJoin('regalia_order ON regalia_order.id = regalia_request.assigned_order')
    ->leftJoin('regalia_group on regalia_group.id = regalia_order.group_id')
    ->where('(regalia_group.id IS NULL OR regalia_group.id <> ?)', [$group->id()]);

// group by identifier
$requesters->group('regalia_request.identifier');

// set up form
$form = new FormWrapper('requests');
$form->setDisplayChildren(false);
$form->button()->setText('Save changes');

// set up table
$table = new PaginatedTable(
    $requesters,
    function (RegaliaRequest $request) use ($form, $group): array {
        $requester = new RegaliaRequester($request->semester(), $request->identifier());
        if (!Regalia::validatePersonInfo($requester->identifier())) {
            // insert a link to update person with missing institution_id
            $select = sprintf(
                '<a href="%s">Enter alma mater</a>',
                new URL('/~regalia/global_regalia_settings/missing_alma_maters.html')
            );
            if ($group->type() == $requester->preferredGroup() && !$group->ordersLocked()) {
                // insert a link to place order here with UNM colors
                $select .= '<br>OR: ';
                $select .= (new CallbackLink(function () use ($requester,$group) {
                    $order = $requester->createUnmOrderIn($group);
                }, null, null, '_frame'))
                    ->setID(md5('use-unm-colors-' . $requester->identifier()))
                    ->addChild('Use UNM');
            }
        } else {
            // create select field for taking an action on this person
            $options = ['nothing' => '--'];
            $default = null;
            // offer option to reuse an existing order linked to this person
            foreach ($requester->requests() as $request) {
                if ($order = $request->order()) {
                    if ($order->group()->id() != $group->id()) {
                        $options['move_' . $order->id()] = 'Move #' . $order->id() . ' from ' . $order->group()->name();
                        // if ($requester->preferredGroup() == $group->type()) $default = 'move_' . $order->id();
                    }
                }
            }
            // look for other existing orders linked to this person ID
            $query = RegaliaOrders::select()
                ->where('semester', $group->semester()->intVal())
                ->where('identifier', $requester->identifier());
            while ($order = $query->fetch()) {
                $options['order_' . $order->id()] = 'Use #' . $order->id() . ' in ' . $order->group()->name();
                if ($group->type() == $requester->preferredGroup()) $default = $default ?? 'keep_' . $group->id();
                if ($order->group()->id() != $group->id() && !$order->group()->ordersLocked() && !$group->ordersLocked()) {
                    $options['move_' . $order->id()] = 'Move #' . $order->id() . ' here';
                    if ($group->type() == $requester->preferredGroup()) $default = $default ?? 'move_' . $group->id();
                }
            }
            // offer options to create orders in this or another order group
            $groups = RegaliaGroups::getBySemester($group->semester());
            foreach ($groups as $g) {
                if ($g->ordersLocked()) continue; // skip option to do anything in groups that are locked
                if ($g->id() == $group->id()) {
                    $options['group_' . $g->id()] = 'New order here';
                }
                if ($g->type() == $requester->preferredGroup()) $default = $default ?? 'group_' . $g->id();
            }
            // set up select field
            $select = (new SELECT($options))
                ->setID(md5($requester->identifier()))
                ->setDefault($default ?? 'nothing');
            $form->addChild($select);
            // event listener
            $form->addCallback(function () use ($select, $requester, $group) {
                // create new order in the specified group and link it to this requester
                if (substr($select->value(), 0, 6) == 'group_') {
                    if ($g = RegaliaGroups::get(intval(substr($select->value(), 6)))) {
                        $requester->createOrderIn($g);
                    }
                }
                // reassign unassigned requests to the specified order
                elseif (substr($select->value(), 0, 6) == 'order_') {
                    if ($order = RegaliaOrders::get(intval(substr($select->value(), 6)))) {
                        $requester->assignOrder($order);
                    }
                }
                // move the given order to this group and assign it to these requests
                elseif (substr($select->value(), 0, 5) == 'move_') {
                    if ($order = RegaliaOrders::get(intval(substr($select->value(), 5)))) {
                        $order->setGroup($group)->save();
                        $requester->assignOrder($order);
                    }
                }
            });
        }
        // return array for table
        return [
            PersonInfo::getFullNameFor($requester->identifier()) ?? $requester->identifier(),
            new PaginatedTable(
                $requester->requests(),
                function (RegaliaRequest $request) {
                    return [
                        sprintf('<a href="%s" target="_blank">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType()),
                        $request->order() ? sprintf('<a href="%s" target="_blank">%s</a>', $request->order()->url(), $request->order()->orderName()) : '<span class="notification notification--error">UNASSIGNED</span>',
                    ];
                }
            ),
            new PaginatedTable(
                RegaliaOrders::select()
                    ->where('semester', $group->semester()->intVal())
                    ->where('identifier', $requester->identifier()),
                function (RegaliaOrder $order): array {
                    return [
                        sprintf('<a href="%s" target="_blank">%s</a>', $order->url(), $order->orderName()),
                        sprintf('<a href="%s" target="_blank">%s</a>', $order->group()->url(), $order->group()->name()),
                    ];
                }
            ),
            $select
        ];
    },
    [
        'Requester',
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
