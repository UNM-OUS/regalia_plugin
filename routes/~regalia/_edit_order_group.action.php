<h1>Edit order group</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroups;

$group = RegaliaGroups::get(Context::arg('id'));
if (!$group) throw new HttpError(404);
Breadcrumb::parent($group->url());

$form = new FormWrapper();

$type = (new Field('Type', new SELECT([
    'normal' => 'normal',
    'platform' => 'platform',
    'marshal' => 'marshal'
])))
    ->setRequired(true)
    ->setDefault($group->type())
    ->addForm($form);

$name = (new Field('Display name'))
    ->setAttribute('placeholder', '[semester] [type]')
    ->addTip('Leave blank to use default naming convention')
    ->setDefault($group->name())
    ->setRequired(true)
    ->addForm($form);

$tams = (new CheckboxField('Tams instead of caps'))
    ->setDefault($group->tams())
    ->addForm($form);

$lockOrders = (new CheckboxField('Lock modification of orders'))
    ->setDefault($group->ordersLocked())
    ->addForm($form);

$lockCancellation = (new CheckboxField('Lock cancellation of orders'))
    ->setDefault($group->cancellationLocked())
    ->addForm($form);

if ($form->ready()) {
    $group->setType($type->value());
    $group->setName($name->value());
    $group->setTams($tams->value());
    $group->setLockOrders($lockOrders->value());
    $group->setLockCancellation($lockCancellation->value());
    $group->save();
    throw new RedirectException($group->url());
}

echo $form;
