<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\regalia\Regalia;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroups;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequest;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequester;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequesters;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequests;

$group = RegaliaGroups::get(intval(Context::url()->actionSuffix()));
if (!$group) throw new HttpError(404);
Breadcrumb::setTopName('Assign extras');
Breadcrumb::parent($group->url());

echo "<h1>Assign extras: " . $group->name() . "</h1>";

// basic request query
$requesters = (RegaliaRequesters::semester($group->semester()))
    ->nonCancelled()
    ->unassigned()
    ->order(null);

// limit to requests of the specified type
if ($group->type() == 'normal') {
    $requesters->where('preferred_group IS NULL');
} else {
    $requesters->like('preferred_group', $group->type());
}

// order to accommodate first/last if specified

// order oldest requests first
$requesters
    ->order('regalia_request.id ASC');

// set up array to hold pairings between person identifiers and extras
/** @var array<string,RegaliaOrder> */
$assignments = [];

// array to store which order IDs have already been used
/** @var array<int,int> */
$assigned_orders = [];

// loop through requesters and make assignments
foreach ($requesters as $requester) {
    // find the best remaining extra for this requester
    $extras = $group->extras()->where('identifier IS NULL')->order(null);
    if ($assigned_orders) $extras->where('regalia_order.id NOT', $assigned_orders);
    $person = Regalia::getFullPersonInfo($requester->identifier());
    $height = intval($person['size_height']);
    $extras->order("CASE WHEN regalia_order.size_height <= $height THEN $height - regalia_order.size_height ELSE 100 + regalia_order.size_height - $height END");
    $extra = $extras->fetch();
    $assigned_orders[$requester->identifier()] = $extra->id();
    $assignments[$requester->identifier()] = $extra;
}

echo '<table>';
echo '<tr><th>&nbsp;</th><th>Person</th><th>Requested</th><th>Assigned</th><th>&nbsp;</th></tr>';
$match_classes = [
    'good' => 'confirmation',
    'fair' => 'warning',
    'poor' => 'danger'
];
foreach ($assignments as $identifier => $extra) {
    $person = Regalia::getFullPersonInfo($identifier);
    $person_height = $person['size_height'];
    $extra_height = $extra ? $extra->height() : 0;
    $match = 'good';
    if ($person_height >= $extra_height) {
        $difference = $person_height - $extra_height;
        if ($difference > 6) $match = 'poor';
        elseif ($difference > 3) $match = 'fair';
    } else {
        $difference = $extra_height - $person_height;
        if ($difference > 3) $match = 'poor';
        elseif ($difference > 2) $match = 'fair';
    }
    echo '<tr>';
    printf(
        '<td>%s #%s<br><span class="notification notification--%s">%s match</span></td>',
        $extra->type(),
        $extra->id(),
        $match_classes[$match],
        $match
    );
    $perks = [];
    $requests = RegaliaRequests::semester(Semesters::current())
        ->where('identifier', $identifier)
        ->nonCancelled();
    printf(
        '<td>%s (%s)<br>%s</td>',
        PersonInfo::getFullNameFor($identifier),
        $identifier,
        new PaginatedTable(
            $requests,
            function (RegaliaRequest $request): array {
                return [
                    sprintf('<a href="%s">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType())
                ];
            }
        )
    );
    printf('<td>%s"</td><td>%s"</td>', $person['size_height'], $extra->height());
    echo '</tr>';
}
echo '</table>';

if (!$assignments) return;
echo new SingleButton(
    'Save these extra assignments',
    function () use ($assignments, $group) {
        foreach ($assignments as $identifier => $extra) {
            $requester = new RegaliaRequester($group->semester(), $identifier);
            $requester->assignExtra($extra);
        }
        Notifications::flashConfirmation('Extra assignments saved');
        throw new RedirectException(new URL('extras:' . Context::url()->actionSuffix()));
    }
);