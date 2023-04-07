<h1>Regalia management</h1>

<?php

use DigraphCMS\Datastore\DatastoreNamespace;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\ColumnSemesterFilteringHeader;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroup;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroups;

ActionMenu::addContextAction(new URL('_add_order_group.html'));

// form for setting regalia deadline
if (Permissions::inMetaGroup('regalia__edit')) {
    $datastore = new DatastoreNamespace('regalia');
    $semester = Semesters::current();
    $form = new FormWrapper('deadline');
    $form->button()->setText('Save deadlines');

    $orderDeadline = (new DatetimeField("$semester order deadline"))
        ->addTip('Deadline after which regalia orders are no longer guaranteed, and will be filled with extras')
        ->addForm($form);
    if ($current = $datastore->value('order-deadline', strval($semester->intVal()))) {
        $orderDeadline->setDefault((new DateTime)->setTimestamp(intval($current)));
    }

    $cancellationDeadline = (new DatetimeField("$semester cancellation deadline"))
        ->addTip('Deadline after which regalia order cancellation may not be possible')
        ->addForm($form);
    if ($current = $datastore->value('cancellation-deadline', strval($semester->intVal()))) {
        $cancellationDeadline->setDefault((new DateTime)->setTimestamp(intval($current)));
    }

    if ($form->ready()) {
        $datastore->set('order-deadline', strval($semester->intVal()), $orderDeadline->value() ? $orderDeadline->value()->getTimestamp() : null);
        $datastore->set('cancellation-deadline', strval($semester->intVal()), $cancellationDeadline->value() ? $cancellationDeadline->value()->getTimestamp() : null);
        Notifications::flashConfirmation('Regalia deadlines saved');
        throw new RefreshException();
    }
    echo $form;
}

// display table of all groups
$groups = RegaliaGroups::select()
    ->order('semester desc')
    ->order('name asc');

$table = new PaginatedTable(
    $groups,
    function (RegaliaGroup $group): array {
        return [
            sprintf('<a href="%s">%s</a>', $group->url(), $group->name()),
            $group->semester(),
            $group->type(),
            $group->orders()->nonCancelled()->count(),
            $group->orders()->cancelled()->count(),
            $group->extras()->nonCancelled()->count(),
        ];
    },
    [
        'Group',
        new ColumnSemesterFilteringHeader('Semester', 'semester'),
        new ColumnStringFilteringHeader('Type', 'type'),
        'Orders',
        'Cancelled',
        'Extras',
    ]
);

echo $table;
