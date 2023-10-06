<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;

/** @var URL */
$url = Context::fields()['url'];

?>
# Regalia information

You have been requested to enter your regalia information so that RSVPs or regalia orders can be created for you.

<a href="<?= $url ?>">Click here to fill out your regalia information</a>

This link will expire in one week.