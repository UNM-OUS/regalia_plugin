<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\ArrayTable;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteField;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Forms\InstitutionInput;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaDegreeField;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaSizeField;
use DigraphCMS_Plugins\unmous\regalia\Regalia;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequest;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequests;

$order = RegaliaOrders::get(intval(Context::url()->actionSuffix()));
if (!$order) throw new HttpError(404);

Breadcrumb::parent($order->group()->url());

echo "<div id='order-interface-wrapper' class='navigation-frame navigation-frame--stateless'>";

echo "<h1>" . $order->semester() . ": " . $order->type() . " #" . $order->id() . "</h1>";
echo new ArrayTable([
    'Person identifier' => $order->identifier(),
    'Name' => $order->firstName() . ' ' . $order->lastName(),
    'Email' => $order->email(),
    'Hat' => $order->hat() ? ($order->tam() ? $order->hatSize() . ' Tam' : 'Cap') : 'None',
    'Hood' => $order->hood() ? implode('<br>', [$order->degreeLevel(), $order->degreeField(), $order->institutionName()]) : 'None',
    'Robe' => $order->robe() ? implode(', ', [$order->degreeLevel(), $order->heightHR(), $order->weight() . 'lbs']) : 'None',
]);

// cancellation tools
echo "<h2>Cancellation</h2>";
if ($order->cancelled()) Notifications::printWarning('Order cancelled');
if ($order->group()->cancellationLocked()) {
    Notifications::printNotice('Order group cancellations are locked');
} else {
    echo "<p>Cancelling or uncancelling here will also immediately update the regalia rental request field value for all associated RSVPs.</p>";
    $requesters =  RegaliaRequests::select()
        ->where('regalia_request.assigned_order', $order->id());
    if ($order->cancelled()) {
        echo (new CallbackLink(function () use ($order, $requesters) {
            while ($request = $requesters->fetch()) {
                $request->parent()->requestRegaliaUncancellation();
            }
            $order->setCancelled(false)->save();
        }, null, '_frame'))
            ->addChild('Un-cancel this order');
    } else {
        echo (new CallbackLink(function () use ($order, $requesters) {
            while ($request = $requesters->fetch()) {
                $request->parent()->requestRegaliaCancellation();
            }
            $order->setCancelled(true)->save();
        }, null, '_frame'))
            ->addChild('Cancel this order');
    }
}

// display what requests this order is being used for
$person = null;
if ($order->identifier()) {
    $person = PersonInfo::fetch($order->identifier());
    echo "<h2>For person <kbd>" . $order->identifier() . "</kbd></h2>";
    $requesters =  RegaliaRequests::select()
        ->where('regalia_request.assigned_order', $order->id());
    echo "<h3>Assigned to requests</h3>";
    echo new PaginatedTable(
        $requesters,
        function (RegaliaRequest $request): array {
            return [
                sprintf('<a href="%s" target="_blank">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType())
            ];
        }
    );
    $other = RegaliaRequests::select()
        ->where('semester', $order->semester()->intVal())
        ->where('regalia_request.assigned_order <> ?', [$order->id()])
        ->where('regalia_request.identifier', $order->identifier());
    if ($other->count()) {
        echo "<h3>Other requests for this person</h3>";
        echo new PaginatedTable(
            $other,
            function (RegaliaRequest $request): array {
                return [
                    sprintf('<a href="%s" target="_blank">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType())
                ];
            }
        );
    }
}

// editing tools
if ($order->group()->ordersLocked()) {
    Notifications::printNotice('Order group is locked, and this order cannot be edited');
} else {
    $regaliaPerson = $order->identifier() ? Regalia::getPersonInfo($order->identifier()) : null;

    // form for editing size
    echo "<h2>Edit order/size</h2>";
    $form = new FormWrapper('size');
    $form->setData('target', '_frame');
    $parts = (new CheckboxListField('Regalia parts', [
        'hat' => 'Cap/tam',
        'robe' => 'Robe',
        'hood' => 'Hood'
    ]))
        ->setDefault(array_filter([
            $order->hat() ? 'hat' : false,
            $order->robe() ? 'robe' : false,
            $order->hood() ? 'hood' : false,
        ]))
        ->addForm($form);
    $size = (new RegaliaSizeField('Size'))
        ->setDefault([
            'height' => $order->height(),
            'weight' => $order->weight(),
            'hat' => $order->hatSize()
        ]);
    $form->addChild($size);
    $form->addCallback(function () use ($parts, $size, $order) {
        $order
            ->setHat(in_array('hat', $parts->value()))
            ->setRobe(in_array('robe', $parts->value()))
            ->setHood(in_array('hood', $parts->value()))
            ->setHeight($size->value()['height'])
            ->setWeight($size->value()['weight'])
            ->setHatSize($size->value()['hat'])
            ->save();
        if ($order->identifier()) {
            Regalia::query()
                ->update('regalia_person', [
                    'needs_hat' => in_array('hat', $parts->value()) ? 1 : 0,
                    'needs_robe' => in_array('robe', $parts->value()) ? 1 : 0,
                    'needs_hood' => in_array('hood', $parts->value()) ? 1 : 0,
                    'size_height' => $size->value()['height'],
                    'size_weight' => $size->value()['weight'],
                    'size_hat' => $size->value()['hat']
                ])
                ->where('identifier', $order->identifier())
                ->execute();
        }
        throw new RefreshException();
    });
    echo $form;

    // form for editing degree info
    echo "<h2>Set degree information</h2>";
    $form = new FormWrapper('degree');
    $form->setData('target', '_frame');
    $degree = new RegaliaDegreeField('Degree');
    if ($regaliaPerson) $degree->setDefault([
        'preset_id' => $regaliaPerson['preset_id'],
        'field_id' => $regaliaPerson['field_id']
    ]);
    $form->addChild($degree);
    $form->addCallback(function () use ($degree, $order) {
        $preset = Regalia::preset($degree->value()['preset_id']);
        $field = Regalia::field($degree->value()['field_id']);
        $order
            ->setDegreeLevel($preset['level'])
            ->setDegreeField($field['field_name'])
            ->setColorBand($field['field_color'])
            ->save();
        if ($order->identifier()) {
            Regalia::query()
                ->update('regalia_person', [
                    'preset_id' => $degree->value()['preset_id'],
                    'field_id' => $degree->value()['field_id']
                ])
                ->where('identifier', $order->identifier())
                ->execute();
        }
        throw new RefreshException();
    });
    echo $form;

    // form for editing alma mater
    echo "<h2>Set alma mater</h2>";
    $form = new FormWrapper('inst');
    $form->setData('target', '_frame');
    $inst = new AutocompleteField('Institution', new InstitutionInput());
    if ($regaliaPerson) $inst->setDefault($regaliaPerson['institution_id']);
    $form->addChild($inst);
    $form->addCallback(function () use ($inst, $order) {
        $institution = Regalia::institution($inst->value());
        $order
            ->setInstitutionName($institution['jostens_name'])
            ->setInstitutionCity($institution['jostens_city'])
            ->setInstitutionState($institution['jostens_state'])
            ->setColorLining($institution['color_lining'])
            ->setColorChevron($institution['color_chevron'])
            ->save();
        if ($order->identifier()) {
            Regalia::query()
                ->update('regalia_person', [
                    'institution_id' => $inst->value()
                ])
                ->where('identifier', $order->identifier())
                ->execute();
        }
        throw new RefreshException();
    });
    echo $form;
}

echo "</div>";

if (!Permissions::inMetaGroup('regalia__admin')) return;

echo '<div class="card navigation-frame navigation-frame--stateless" id="order-deletion-interface" data-target="_top">';
echo "<h2>Delete</h2>";
echo "<p>Delete this order, as if it had never existed. This action cannot be undone.</p>";
echo "<p>If this order was assigned to any requests, those requests will be unassigned, but otherwise left intact.</p>";

if ($order->group()->ordersLocked() || $order->group()->cancellationLocked()) {
    Notifications::printWarning('If this order has already been sent to the bookstore/Jostens, use this tool with <strong>extreme</strong> caution. this should generally only be done if the order was lost in transit after ordering.');
}

if (Context::arg('delete') != 1) {
    printf("<a href='%s' class='button button--warning' data-target='_frame'>Delete order</a>", new URL('?delete=1'));
} else {
    $buttons = new ButtonMenu();
    $buttons->setTarget('_top');
    $buttons->addButton(new ButtonMenuButton('Yes, delete this order', function () use ($order) {
        $url = $order->group()->url();
        $order->delete();
        Notifications::flashConfirmation(sprintf('%s deleted', $order->orderName()));
        throw new RedirectException($url);
    }, ['button--danger']));
    $buttons->addButton(new ButtonMenuButton('Cancel deletion', function () {
        throw new RedirectException(new URL('?delete=0'));
    }));
    echo "<p>Are you sure? This action cannot be undone.</p>";
    echo $buttons;
}

echo "</div>";
