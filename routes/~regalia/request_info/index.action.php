<h1>Request someone's regalia info</h1>
<p>
    You can use this tool to request a person's regalia information before creating an RSVP for them.
    The recipient will get an email asking them to fill out their regalia information, with a link to a form where
    they'll only have to fill out the necessary sizing information based on what they need.
    These requests expire after a week, and you will get an email when the recipient fills out their information.
</p>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Email;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\EmailOrNetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Regalia;
use DigraphCMS_Plugins\unmous\regalia\RegaliaInfoRequests;

$form = new FormWrapper();
$form->button()->setText('Send regalia info request');

$identifier = (new Field('NetID or email', new EmailOrNetIDInput()))
    ->addTip('This field should identify the person this regalia is <strong>actually for</strong>, and not an assistant or someone who will be filling this out on their behalf')
    ->setDefault(Context::arg('default'))
    ->setRequired(true)
    ->addForm($form);

$additional_email = (new Field('Additional/alternate email', new Email()))
    ->addTip('The email or NetID above will always be included, but you can use this field to include an additional or alternate address, such as for HSC users or to include someone\'s assistant.')
    ->setRequired(false)
    ->addForm($form);

if ($form->submitted()) {
    $pinfo = PersonInfo::getFor($identifier->value(), 'regalia') === false;
    $existing = Regalia::getPersonInfo($identifier->value());
    $valid = $existing && Regalia::validatePersonInfo($identifier->value());
    if ($pinfo && $existing && $valid) {
        $confirm = (new CheckboxField('Are you sure? This person appears to already have an existing and valid regalia order on file.'))
            ->setRequired(true);
    }
}

if ($form->ready()) {
    RegaliaInfoRequests::create($identifier->value(), $additional_email->value());
    Notifications::flashConfirmation('Request created, the email will send in the next few minutes');
    throw new RedirectException(new URL('past_requests.html'));
}

echo $form;
