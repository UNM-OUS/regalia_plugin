<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Forms\RegaliaRequestField;
use DigraphCMS_Plugins\unmous\regalia\RegaliaInfoRequests;

$uuid = Context::url()->actionSuffix();

if (!RegaliaInfoRequests::exists($uuid)) throw new HttpError(404);
if (RegaliaInfoRequests::expired($uuid)) throw new HttpError(404, 'Request link has expired');
Context::response()->template('minimal.php');
$id = RegaliaInfoRequests::identifier($uuid);

$name = PersonInfo::getFullNameFor($id);
if ($name) $name = "$name (<kbd>$id</kbd>)";
else $name = "<kbd>$id</kbd>";
echo "<h1>Regalia information for:<br>$name</h1>";

$form = new FormWrapper();
$form->button()->setText('Confirm settings');

$field = (new RegaliaRequestField('Your regalia needs', $id))
    ->setDefault(PersonInfo::getFor($id, 'regalia') ?? true)
    ->addForm($form);

$field->checkbox()
    ->label()
    ->setText('I do not own my own complete set of regalia, and will need to rent it if it is required');

if ($form->ready()) {
    // send email to creator
    RegaliaInfoRequests::notifyCreator($uuid);
    // flash and bounce to home
    Notifications::flashConfirmation("Thank you for entering your regalia needs, they have been saved and will be used for future regalia orders for <kbd>$id</kbd>");
    throw new RedirectException(new URL('/'));
}

echo $form;
