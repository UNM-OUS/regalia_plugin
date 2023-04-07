<h1>Add new field of study</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

$jField = Regalia::query()
    ->from('jostens_field')
    ->where('id = ?', [Context::arg('jid')])
    ->fetch();

if (!$jField) throw new HttpError(400);

Notifications::printNotice(sprintf(
    "This new field will reference the Jostens field: %s (%s)",
    $jField['field_name'],
    $jField['field_color']
));

$form = new FormWrapper();

$label = (new Field('Display name of field'))
    ->setRequired(true);

$form->addChild($label);
$form->addCallback(function () use ($label) {
    Regalia::query()
        ->insertInto(
            'regalia_field',
            [
                'label' => $label->value(),
                'jostens_id' => Context::arg('jid'),
                'deprecated' => false
            ]
        )->execute();
    throw new RedirectException(new URL('./'));
});

echo $form;
