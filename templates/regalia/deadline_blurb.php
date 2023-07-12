<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

/** @var string|null */
$for = Context::fields()['for'];
$cancelUrl = (new URL('/cancel_my_regalia/'));
if ($for) $cancelUrl->arg('for', $for);

/** @var DateTime|null */
$orderDeadline = Regalia::orderDeadline(Context::fields()['semester']);

/** @var DateTime|null */
$cancellationDeadline = Regalia::cancellationDeadline(Context::fields()['semester']);

$tbd = '<span class="notification notification--neutral">date TBD</span>';

?>
<h2>Faculty regalia rental deadlines</h2>
<p>
    Personalized regalia orders <?php echo ($orderDeadline && ($orderDeadline->getTimestamp() < time())) ? 'were' : 'are'; ?>    scheduled to be ordered from Jostens
    <strong><?php echo $orderDeadline ? Format::date($orderDeadline) : $tbd; ?></strong>.
    Any orders placed after will be placed on a waitlist and be filled using extra regalia in the closest available size.
</p>
<p>
    If you have RSVPed to events but would like to cancel your regalia rental, please
    <a href="<?php echo $cancelUrl ?>">cancel your regalia orders</a>
    by <strong><?php echo $cancellationDeadline ? Format::date($cancellationDeadline) : $tbd; ?></strong>
    for the best chance at successful cancellation.
</p>
