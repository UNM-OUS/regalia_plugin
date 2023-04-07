<h1>Add order group</h1>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\SemesterField;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroup;

$form = new FormWrapper();

$semester = (new SemesterField('Semester', 0, 10, true))
    ->addTip('Cannot be changed once set')
    ->setRequired(true)
    ->addForm($form);

$type = (new Field('Type', new SELECT([
    'normal' => 'normal',
    'platform' => 'platform',
    'marshal' => 'marshal',
    'north' => 'north',
])))
    ->setRequired(true)
    ->addForm($form);

$name = (new Field('Display name'))
    ->setAttribute('placeholder', '[semester] [type]')
    ->addTip('Leave blank to use default naming convention')
    ->addForm($form);

$tams = (new CheckboxField('Tams instead of caps'))
    ->addForm($form);

if ($form->ready()) {
    $group = RegaliaGroup::create(
        $name->value() ? $name->value() : $semester->value() . ' ' . $type->value(),
        $type->value(),
        $semester->value(),
        $tams->value()
    );
    throw new RedirectException($group->url());
}

echo $form;
