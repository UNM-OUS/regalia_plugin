<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequest;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequests;

/** @var string */
$for = Context::fields()['for'];

/** @var Semester */
$semester = Context::fields()['semester'];

$orders = RegaliaOrders::select()
    ->where('semester', $semester->intVal())
    ->where('identifier', $for);
$orderCount = $orders->count();

$cancelledOrderCount = RegaliaOrders::select()
    ->where('semester', $semester->intVal())
    ->where('identifier', $for)
    ->cancelled()
    ->count();

$requesters = RegaliaRequests::semester($semester)
    ->where('identifier', $for);
$requestCount = $requesters->count();

$cancelledRequestCount = RegaliaRequests::semester($semester)
    ->where('identifier', $for)
    ->cancelled()
    ->count();

if (!$orders->count() && !$requesters->count()) return;

if ($orderCount) {
    echo "<h2>Rental orders placed</h2>";
    if ($cancelledRequestCount && !$cancelledOrderCount) {
        if ($cancelledRequestCount == $requesters->count()) {
            Notifications::printNotice(
                'Regalia cancellation requests recorded.<br>' .
                    'Your regalia rental will be cancelled soon if possible, as long as you have no RSVPs with regalia requests and the Jostens cancellation deadline has not passed.'
            );
        } else {
            if (Context::url()->route() != 'cancel_my_regalia') {
                Notifications::printNotice(
                    'You still have one or more active regalia rental requests.<br>' .
                        'If you would like to fully cancel your regalia rental this semester, you must either cancel all RSVPs or regalia requests, or use the <a href="' .
                        (new URL('/cancel_my_regalia/?for=' . $for))->utm('graduation-site', 'website', 'self-service-regalia-cancellation-section') . '">regalia rental cancellation tool</a>.'
                );
            } else {
                Notifications::printWarning(
                    'You have a mix of cancelled and uncancelled regalia requests.<br>' .
                        'Any uncancelled RSVPs will have their regalia requests reactivated automatically unless you cancel all your RSVPs or regalia requests.'
                );
            }
        }
    }
    if ($cancelledOrderCount) {
        Notifications::printNotice(
            'Your regalia rentals have been cancelled.<br>' .
                'If you reactivate any of your requests your rental order will be reinstated soon afterwards if possible.'
        );
    }
    echo new PaginatedTable(
        $orders,
        function (RegaliaOrder $order) use ($orderCount): array {
            return [
                $order->cancelled() ? '<span class="notification notification--error">CANCELLED</span>' : '<span class="notification notification--confirmation">ORDERED</span>',
                $order->group()->name(),
                sprintf('%s #%s', $order->type(), $order->id()),
                $orderCount == 1
                    ? ''
                    : new PaginatedTable(
                        RegaliaRequests::select()->where('assigned_order', $order->id()),
                        function (RegaliaRequest $request): array {
                            return [
                                sprintf('<a href="%s">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType()),
                                $request->cancelled() ? '<span class="notification notification--error">CANCELLED</span>' : '<span class="notification notification--confirmation">ORDERED</span>',
                            ];
                        }
                    )
            ];
        }
    );
} elseif ($cancelledRequestCount) {
    if ($cancelledRequestCount == $requesters->count()) {
        Notifications::printNotice(
            'Regalia cancellation requests have been recorded.<br>' .
                'No regalia rentals will be placed for you as long as you have no other RSVPs with regalia requests and the Jostens cancellation deadline has not passed.'
        );
    } else {
        Notifications::printWarning(
            'You still have one or more active regalia rental requests.<br>' .
                'If you would like to fully cancel your regalia rental this semester, you must either cancel all RSVPs that are requesting it or use the <a href="' .
                (new URL('/cancel_my_regalia/?for=' . $for))->utm('graduation-site', 'website', 'self-service-regalia-cancellation-section') . '">regalia rental cancellation tool</a>.'
        );
    }
}

if ($requesters->count()) {
    echo "<h2>Regalia requested for</h2>";
    echo new PaginatedTable(
        $requesters,
        function (RegaliaRequest $request): array {
            return [
                $request->cancelled() ? '<span class="notification notification--error">CANCELLED</span>' : '<span class="notification notification--confirmation">ACTIVE</span>',
                sprintf('<a href="%s">%s</a>', $request->parent()->url(), $request->parent()->regaliaOrderType()),
                $request->parent()->url()->path() == Context::url()->path() ? '<span class="notification notification--notice">YOU ARE HERE</span>' : '',
            ];
        }
    );
}
