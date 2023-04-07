<h1>Add new institution</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

$jInstitution = Regalia::query()
    ->from('jostens_institution')
    ->where('id = ?', [Context::arg('jid')])
    ->fetch();

if (!$jInstitution) throw new HttpError(400);

Notifications::printNotice(sprintf(
    "This new institution will reference the Jostens hood book school:<br>%s<br>Lining: %s<br>Chevron: %s",
    implode(', ', array_filter([$jInstitution['institution_name'], $jInstitution['institution_city'], $jInstitution['institution_state']], function ($e) {
        return !!$e;
    })),
    $jInstitution['institution_color_lining1'],
    $jInstitution['institution_color_chevron1']
));

$form = new FormWrapper();

$label = (new Field('Display name of institution'))
    ->setRequired(true);

$form->addChild($label);
$form->addCallback(function () use ($label) {
    Regalia::query()
        ->insertInto(
            'regalia_institution',
            [
                'label' => $label->value(),
                'jostens_id' => Context::arg('jid'),
                'deprecated' => 0
            ]
        )->execute();
    throw new RedirectException(new URL('./'));
});

echo $form;
