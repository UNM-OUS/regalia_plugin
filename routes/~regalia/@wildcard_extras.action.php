<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
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

// sort requesters
/** @var RegaliaRequester[] */
$requesters = $requesters->fetchAll();
usort($requesters, function (RegaliaRequester $a, RegaliaRequester $b) {
    // next sort by "priority" of requests, higher total priority first
    $a_priority = $a->priority();
    $b_priority = $b->priority();
    if ($a_priority != $b_priority) {
        return $b_priority - $a_priority;
    }
    // sort by age otherwise, first-come first-serve, so lowest ID first
    $a_id = INF;
    $b_id = INF;
    foreach ($a->requests() as $request) {
        $a_id = min($a_id, $request->id());
    }
    foreach ($b->requests() as $request) {
        $b_id = min($b_id, $request->id());
    }
    if ($a_id != $b_id) {
        return $a_id - $b_id;
    }
    // return zero if all else fails
    return 0;
});

// set up array to hold pairings between person identifiers and extras
/** @var array<string,RegaliaOrder> */
$assignments = [];

// array to store which order IDs have already been used
/** @var array<int,int> */
$assigned_orders = [];

// array to store requests that didn't order a robe. these need to be fulfilled, but last.
/** @var RegaliaRequester[] $no_robe */
$no_robe = [];

// loop through requesters and make assignments
/** @var RegaliaRequester $requester */
foreach ($requesters as $requester) {
    $person = Regalia::getFullPersonInfo($requester->identifier());
    // if requester doesn't need a robe, put off assigning them an extra
    if ($person['needs_robe'] != 1) {
        $no_robe[] = $requester;
        continue;
    }
    // get all unassigned extras
    $extras = $group->extras()->where('identifier IS NULL')->order(null);
    // filter out the ones that we've tentatively assigned here
    if ($assigned_orders) $extras->where('regalia_order.id NOT', $assigned_orders);
    // break if the number of unassigned extras is the same as the number of no_robe extras, so those can be assigned last
    if ($extras->count() == count($no_robe)) {
        break;
    }
    // order by how well they fit this person
    $height = intval($person['size_height']);
    $extras->order("CASE WHEN regalia_order.size_height <= $height THEN $height - regalia_order.size_height ELSE 100 + regalia_order.size_height - $height END");
    // try each one until we get one that works well for this person
    while ($extra = $extras->fetch()) {
        if ($person['needs_hat'] == 1 && !$extra->hat()) continue;
        if ($person['needs_robe'] == 1 && !$extra->robe()) continue;
        if ($person['needs_hood'] == 1 && !$extra->hood()) continue;
        $assigned_orders[$requester->identifier()] = $extra->id();
        $assignments[$requester->identifier()] = $extra;
        break;
    }
}

// assign no_robe orders last, because their size doesn't matter
/** @var RegaliaRequester $requester */
foreach ($no_robe as $requester) {
    $person = Regalia::getFullPersonInfo($requester->identifier());
    // get all unassigned extras
    $extras = $group->extras()->where('identifier IS NULL')->order(null);
    // filter out the ones that we've tentatively assigned here
    if ($assigned_orders) $extras->where('regalia_order.id NOT', $assigned_orders);
    // try each one until we get one that works well for this person
    while ($extra = $extras->fetch()) {
        if ($person['needs_hat'] == 1 && !$extra->hat()) continue;
        if ($person['needs_robe'] == 1 && !$extra->robe()) continue;
        if ($person['needs_hood'] == 1 && !$extra->hood()) continue;
        $assigned_orders[$requester->identifier()] = $extra->id();
        $assignments[$requester->identifier()] = $extra;
        break;
    }
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
        '<td>%s<br><span class="notification notification--%s">%s match</span></td>',
        $extra->orderName(),
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
                    sprintf('<a href="%s">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType()),
                    $request->parent()->regaliaRequestPriority()
                ];
            }
        )
    );
    printf('<td>%s"</td><td>%s"</td>', $person['size_height'], $extra->height());
    echo '</tr>';
}
echo '</table>';

if (!$assignments) return;
echo (new CallbackLink(function () use ($assignments, $group) {
    foreach ($assignments as $identifier => $extra) {
        $requester = new RegaliaRequester($group->semester(), $identifier);
        $requester->assignExtra($extra);
    }
    Notifications::flashConfirmation('Extra assignments saved');
}))
    ->addClass('button')
    ->addChild('Save these extra assignments');
