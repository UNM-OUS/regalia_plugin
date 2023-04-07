<h1>Edit institution</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

$institution = Regalia::query()
    ->from('regalia_institution')
    ->where('id = ?', [Context::arg('id')])
    ->fetch();

if (!$institution) throw new HttpError(400);

$form = new FormWrapper();

$label = (new Field('Display name of institution'))
    ->setDefault($institution['label'])
    ->setRequired(true);

$form->addChild($label);
$form->addCallback(function () use ($label) {
    Regalia::query()
        ->update(
            'regalia_institution',
            [
                'label' => $label->value()
            ],
            Context::arg('id')
        )->execute();
    throw new RedirectException(new URL('./'));
});

echo $form;
