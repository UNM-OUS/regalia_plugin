<h1>Add degree preset</h1>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\Autocomplete\AutocompleteField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\SELECT;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Forms\DegreeFieldInput;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

$form = new FormWrapper();

$label = (new Field('Label'))
    ->setRequired(true)
    ->addTip('The name that will be displayed to users in the first step of selecting their degree');

$weight = (new Field('Weight'))
    ->setRequired(true)
    ->addTip('A number to determine where this field should be displayed in the list')
    ->setDefault(100);
$weight->input()->setAttribute('type', 'number'); // @phpstan-ignore-line

$level = (new Field('Level', new SELECT([
    'DOCTOR' => 'Doctoral/Terminal',
    'MASTER' => 'Master',
    'BACHELOR' => 'Bachelor',
    'ASSOCIATE' => 'Associate'
])))
    ->setRequired(true)
    ->addTip('Not displayed to user, only used for Jostens reports');

$field = (new AutocompleteField('Degree field', new DegreeFieldInput()))
    ->setRequired(false)
    ->addTip('Leave this field blank to allow the user to select their own degree field');

$form->addChild($label)
    ->addChild($weight)
    ->addChild($level)
    ->addChild($field);

if ($form->ready()) {
    Regalia::query()
        ->insertInto(
            'regalia_preset',
            [
                'label' => $label->value(),
                'weight' => $weight->value(),
                'level' => $level->value(),
                'field' => $field->value(),
                'deprecated' => 0,
            ]
        )->execute();
    throw new RedirectException(new URL('./'));
}

echo $form;
