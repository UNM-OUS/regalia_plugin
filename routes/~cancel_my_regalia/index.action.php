<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequester;

Permissions::requireAuth();

$netIDs = OUS::userNetIDs();
if ($for = strip_tags(strtolower(Context::arg('for') ?? ''))) {
    if (!in_array($for, $netIDs) && !Permissions::inMetaGroups(['regalia__edit', 'events__signupothers'])) {
        throw new RedirectException(new URL('./'));
    }
    $for = [$for];
} else {
    $for = $netIDs;
}

if (!$for) Notifications::printError('No user specified');

foreach ($for as $identifier) {
    echo "<div class='card card--light navigation-frame navigation-frame--stateless' id='cancel-regalia-" . md5($identifier) . "'>";
    echo "<h1>" . Semesters::current() . " regalia for <code>$identifier</code></h1>";
    $requester = new RegaliaRequester(Semesters::current(), $identifier);
    if (!$requester->requests()) Notifications::printConfirmation('No regalia requests have been recorded, there is nothing to cancel');
    foreach ($requester->requests() as $request) {
        if (!$request->cancelled()) {
            echo (new CallbackLink(
                function () use ($requester) {
                    foreach ($requester->requests() as $request) {
                        $request->parent()->requestRegaliaCancellation();
                    }
                }
            ))->setID(md5($identifier))
                ->addChild("Cancel all regalia requests below")
                ->addClass('button button--warning')
                ->setData('target', '_frame');
            break;
        }
    }
    echo Templates::render('regalia/self-service-section.php', ['for' => $identifier, 'semester' => Semesters::current()]);
    echo "</div>";
}
