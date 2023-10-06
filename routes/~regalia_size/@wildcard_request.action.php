<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaRequestField;
use DigraphCMS_Plugins\unmous\regalia\RegaliaInfoRequests;

$uuid = Context::url()->actionSuffix();

if (!RegaliaInfoRequests::exists($uuid)) throw new HttpError(404);
if (RegaliaInfoRequests::expired($uuid)) throw new HttpError(404, 'Request link has expired');
Context::response()->template('minimal');

$form = new FormWrapper();
$form->button()->setText('Confirm settings');

$field = new RegaliaRequestField('Your regalia needs', RegaliaInfoRequests::identifier($uuid));

if ($form->ready()) {
    // TODO send email to creator
    // flash and bounce to home
    $id = RegaliaInfoRequests::identifier($uuid);
    Notifications::flashConfirmation("Thank you for entering your regalia needs, they have been saved and will be used for future regalia orders for <code>$id</code>");
    throw new RedirectException(new URL('/'));
}

echo $form;
