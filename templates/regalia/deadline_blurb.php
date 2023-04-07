<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

/** @var string|null */
$for = Context::fields()['for'];
$cancelUrl = (new URL('/cancel_my_regalia/'))->utm('graduation-site', 'website', 'regalia-deadline-blurb');
if ($for) $cancelUrl->arg('for', $for);

/** @var DateTime|null */
$orderDeadline = Regalia::orderDeadline(Context::fields()['semester']);
if (!$orderDeadline) return;

/** @var DateTime|null */
$cancellationDeadline = Regalia::cancellationDeadline(Context::fields()['semester']);

?>
<div class="card card--small">
    <h2>Faculty regalia rental deadlines</h2>
    <p>
        Personalized regalia orders <?php echo $orderDeadline->getTimestamp() > time() ? 'are' : 'were'; ?>
        scheduled to be ordered from Jostens
        <strong><?php echo Format::date($orderDeadline); ?></strong>
        at <strong><?php echo Format::time($orderDeadline); ?></strong>.
        Any orders placed after will be placed on a waitlist and be filled using extra regalia in the closest available size.
    </p>
    <p>
        If you have RSVPed to events but would like to cancel your regalia rental, please
        <a href="<?php echo $cancelUrl ?>">cancel your regalia orders</a>
        by <strong><?php echo Format::date($cancellationDeadline ?? $orderDeadline); ?></strong>
        at <strong><?php echo Format::time($cancellationDeadline ?? $orderDeadline); ?></strong>
        for the best chance at successful cancellation.
        We will do our best to cancel your order with Jostens or reuse your order to fill waitlisted regalia requests,
        but if we are unable to do so your departmental convocations may still be charged for your regalia if you do not attend Commencement.
    </p>
</div>