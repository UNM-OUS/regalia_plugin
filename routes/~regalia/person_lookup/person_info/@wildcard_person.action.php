<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaInformationForm;

Context::response()->filename('regalia-info.html');
printf('<h1>Regalia Info: %s</h1>', PersonInfo::getFullNameFor(Context::url()->actionSuffix()) ?? Context::url()->actionSuffix());

if (Context::arg('form') !== 'edit') throw new RedirectException(new URL('&form=edit'));

$form = new RegaliaInformationForm(Context::url()->actionSuffix());
$form->setID('form');
echo $form;
