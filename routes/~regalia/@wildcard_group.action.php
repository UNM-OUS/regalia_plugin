<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\ButtonMenus\ButtonMenu;
use DigraphCMS\UI\ButtonMenus\ButtonMenuButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroups;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrderTable;

$group = RegaliaGroups::get(intval(Context::url()->actionSuffix()));
if (!$group) throw new HttpError(404);
Breadcrumb::top($group->url());

ActionMenu::addContextAction(new URL('_edit_order_group.html?id=' . $group->id()));
ActionMenu::addContextAction(new URL('requests:' . $group->id()), 'Regalia requests');

if (!$group->ordersLocked()) {
    ActionMenu::addContextAction(new URL('_add_extras.html?id=' . $group->id()));
} else {
    ActionMenu::addContextAction(new URL('extras:' . $group->id()), 'Assign extras');
}

echo "<h1>" . $group->name() . " (" . $group->semester() . ")</h1>";

if ($group->ordersLocked()) Notifications::printNotice('Creating and modifying orders is locked');
if ($group->cancellationLocked()) Notifications::printNotice('Cancellation of orders is locked');

echo "<h2>Orders</h2>";
echo new RegaliaOrderTable($group->orders()->nonCancelled(), $group->name() . ' orders');

echo "<h2>Extras</h2>";
echo new RegaliaOrderTable($group->extras()->nonCancelled(), $group->name() . ' extras');

echo "<h2>Cancelled orders</h2>";
echo new RegaliaOrderTable($group->orders()->cancelled(), $group->name() . ' orders');

echo "<h2>Cancelled extras</h2>";
echo new RegaliaOrderTable($group->extras()->cancelled(), $group->name() . ' orders');

// deletion tool

if (!Permissions::inMetaGroup('regalia__admin')) return;

echo '<div class="card navigation-frame navigation-frame--stateless" id="order-deletion-interface" data-target="_top">';
echo "<h2>Delete</h2>";
echo "<p>Delete this order group, as if it and its orders never existed. This action cannot be undone</p>";

if (Context::arg('delete') != 1) {
    printf("<a href='%s' class='button button--warning' data-target='_frame'>Delete order group</a>", new URL('?delete=1'));
} else {
    $buttons = new ButtonMenu();
    $buttons->setTarget('_top');
    $buttons->addButton(new ButtonMenuButton('Yes, delete this group', function () use ($group) {
        $group->delete();
        Notifications::flashConfirmation(sprintf('Group %s deleted', $group->name()));
        throw new RedirectException(new URL('./'));
    }, ['button--danger']));
    $buttons->addButton(new ButtonMenuButton('Cancel deletion', function () {
        throw new RedirectException(new URL('?delete=0'));
    }));
    echo "<p>Are you sure? This action cannot be undone.</p>";
    echo $buttons;
}

echo "</div>";
