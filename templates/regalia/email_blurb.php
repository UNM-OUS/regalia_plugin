<?php

use DigraphCMS\Context;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

?>
<h2>Your regalia rental</h2>

<p>
    Regalia rental orders are placed with the bookstore several weeks before the event.
    If you are ordering past the date regalia begins being sent to Jostens
    <?php
    if ($deadline = Regalia::orderDeadline(Context::fields()['semester'] ?? Semesters::current())) {
        echo '(currently set to ' . Format::datetime($deadline, true, true) . ')';
    } else {
        echo '(currently not known, check the signup page later to see if it has been set)';
    }
    ?>
    then your order is not guaranteed.
</p>

<p>
    If you would like to cancel your regalia rental, you can use the
    <a href="<?php echo (new URL('/cancel_my_regalia/'))->utm('regalia-email', 'auto-email', 'regalia-email-blurb'); ?>">cancel my regalia page</a>.
    <?php
    if ($deadline = Regalia::cancellationDeadline(Context::fields()['semester'] ?? Semesters::current())) {
        printf('Cancellation may not be possible after %s.', Format::datetime($deadline, true, true));
    } else {
        echo 'Cancellation deadline is not known yet, check the signup page later to see if it has been set.';
    }
    ?>
</p>

<p>
    You will be notified in a separate email with instructions for picking up your regalia.
</p>