<h1>Regalia global settings</h1>

<p>
    These regalia tools will update settings across all sites using the regalia plugin.
</p>

<?php

use DigraphCMS\UI\ActionMenu;
use DigraphCMS\UI\Templates;

ActionMenu::hide();

echo Templates::render(
    'content/toc.php',
);
