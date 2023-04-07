<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

$colors = [];
$q = Regalia::query()
    ->from('jostens_institution')
    ->select(null)
    ->select('DISTINCT institution_color_chevron1, institution_color_lining1');
while ($c = $q->fetch()) {
    $colors[$c['institution_color_chevron1']] = $c['institution_color_chevron1'];
    $colors[$c['institution_color_lining1']] = $c['institution_color_lining1'];
}
$colors = array_filter($colors, '\\is_string');
asort($colors);

$form = new FormWrapper();

$colors = new CheckboxListField('Limit to colors', $colors);
$form->addChild($colors);
$default = explode('|', Context::arg('default') ?? '');
$default = array_filter($default, function ($e) {
    return !!$e;
});
if ($default) $colors->setDefault($default);

$form->addCallback(function () use ($colors) {
    throw new RedirectException(
        new URL('./?colors=' . implode('|', $colors->value()))
    );
});

echo $form;
