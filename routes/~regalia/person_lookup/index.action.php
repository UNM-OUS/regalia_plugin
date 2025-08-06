<h1>Regalia person info lookup</h1>
<p>
    This page allows you to look up a person by their identifier (NetID for UNM-affiliated people, email address
    otherwise) and view their regalia preferences as well as their past orders on this specific site.
</p>
<?php

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Forms\EmailOrNetIDInput;
use DigraphCMS_Plugins\unmous\ous_digraph_module\People\PositionInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Regalia;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;

$form = new FormWrapper();
$form->button()->settext('Lookup');
$identifier = (new Field('NetID or email', new EmailOrNetIDInput()))
    ->setRequired(true)
    ->addForm($form);
echo $form;

if ($form->ready()) {
    echo '<div class="card">';
    $position = PositionInfo::search($identifier->value());
    $person_info = PersonInfo::fetch($identifier->value());
    $name = $person_info->fullName() ?? $identifier->value();
    echo "<h2>$name</h2>";
    if ($position->staff || $position->faculty) {
        Notifications::printConfirmation('Current position: ' . implode(', ', array_filter([
                $position->faculty ? 'Faculty' : false,
                $position->staff ? 'Staff' : false,
                $position->title,
                $position->department,
                $position->org,
            ])));
    } else {
        Notifications::printWarning('No current UNM position found');
    }
    echo '<h3>Regalia needs</h3>';
    $info = Regalia::getPersonInfo($identifier->value());
    $editUrl = new URL('person_info/person:' . $identifier->value());
    $requestUrl = new URL('../request_info/');
    $requestUrl->arg('default', $identifier->value());
    $regalia_needs = $person_info['regalia'];
    if (is_null($regalia_needs)) {
        Notifications::printWarning(sprintf(
            'This person\'s regalia needs are unknown.<br><a href="%s">Request info</a><br><a href="%s">Add it yourself now</a>',
            $requestUrl,
            $editUrl
        ));
    } else {
        Notifications::printConfirmation(sprintf('This person <strong>%s</strong> own their own regalia', $regalia_needs ? 'does not' : 'does'));
    }
    if ($info) {
        $preset = Regalia::preset($info['preset_id']);
        $field = Regalia::field($info['field_id']);
        $institution = $info['institution_id'] ? Regalia::institution($info['institution_id']) : null;
        if (!Regalia::validatePersonInfo($identifier->value(), true)) {
            Notifications::printError(sprintf(
                'Regalia information needs to be updated due to changes to the form or available options.<br><a href="%s">Request an update from the person</a><br><a href="%s">Update it yourself now</a>',
                $requestUrl,
                $editUrl
            ));
        }
        $display = [];
        $display [] = sprintf(
            '<div><strong>Needed regalia pieces:</strong> %s</div>',
            implode(', ', array_filter([
                $info['needs_hat'] ? 'hat' : false,
                $info['needs_robe'] ? 'robe' : false,
                $info['needs_hood'] ? 'hood' : false,
            ]))
        );
        if (($info['needs_robe'] || $info['needs_hood']) && $preset) $display[] = sprintf(
            '<div><strong>Degree: </strong> %s%s</div>',
            @$preset['label'],
            !@$preset['field'] ? ' (' . @$field['label'] . ')' : ''
        );
        if (($info['needs_robe'] || $info['needs_hood']) && $institution) $display[] = sprintf(
            '<div><strong>Alma mater: </strong> %s</div>',
            $institution['label']
        );
        else if ($info['institution_notfound']) {
            $display[] = '<div><strong>Alma mater: </strong> Marked as "not found."</div>';
        }
        if ($info['needs_robe'] && $info['size_height']) $display[] = sprintf(
            '<div><strong>Robe size: </strong> %s\' %s", %slbs</div>',
            floor($info['size_height'] / 12),
            $info['size_height'] % 12,
            abs(intval($info['size_weight'])),
        );
        if ($info['needs_hat'] && $info['size_hat']) $display[] = sprintf(
            '<div><strong>Hat size: </strong> %s</div>',
            $info['size_hat']
        );
        Notifications::printConfirmation(implode('', array_filter($display)));
    }
    echo '<h3>Past orders</h3>';
    $orders = \DigraphCMS_Plugins\unmous\regalia\RegaliaOrders::select()
        ->where('identifier', $identifier->value())
        ->order('group_id DESC');
    echo new PaginatedTable(
        $orders,
        function (RegaliaOrder $order): array {
            return [
                $order->group()->url()->html(),
                sprintf('<a href="%s">%s</a>', $order->url(), $order->orderName()),
                $order->cancelled() ? 'Yes' : 'No'
            ];
        },
        [
            'Group',
            'Order',
            'Cancelled'
        ]
    );
    echo '</div>';
}