<h1>Import fields of study</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\Cron\DeferredProgressBar;
use DigraphCMS\Cron\SpreadsheetJob;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\UploadSingle;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

if ($job = Context::arg('job')) {
    echo (new DeferredProgressBar($job))
        ->setBounceAfter(new URL('./'));
    return;
}

$form = new FormWrapper();

$file = (new Field('Field of study spreadsheet', $upload = new UploadSingle()))
    ->setRequired(true);
$form->addChild($file);

if ($form->ready()) {
    $f = $upload->value();
    // mark all jostens institutions as deprecated, they will be reactivated 
    // row by row as the spreadsheet processes
    Regalia::query()->update(
        'jostens_field',
        ['field_deprecated' => true]
    )->where('field_deprecated <> 1')
        ->execute();
    // create a deferred execution job to process spreadsheet row by row
    $job = new SpreadsheetJob(
        $f['tmp_name'],
        function (array $row) {
            // fetch existing field with this name, if applicable
            $existing = Regalia::query()
                ->from('jostens_field')
                ->where('field_name = ?', [$row['name']])
                ->limit(1)
                ->fetch();
            // if an institution with this name/city/state exists, update it,
            // set its colors to what's in the hood book and deprecation to false
            if ($existing) {
                Regalia::query()->update(
                    'jostens_field',
                    [
                        'field_color' => $row['color'],
                        'field_deprecated' => false
                    ],
                    $existing['id']
                )->execute();
            }
            // otherwise insert a new institution
            else {
                // insert new institution into Jostens table
                $id = Regalia::query()->insertInto(
                    'jostens_field',
                    [
                        'field_name' => $row['name'],
                        'field_color' => ucwords(strtolower($row['Color'])),
                        'field_deprecated' => false
                    ],
                )->execute();
                // create the default reference to this institution
                Regalia::query()->insertInto(
                    'regalia_field',
                    [
                        'label' => ucwords(strtolower($row['name'])),
                        'jostens_id' => $id,
                        'deprecated' => false
                    ]
                )->execute();
            }
        },
        pathinfo($f['name'], PATHINFO_EXTENSION)
    );
    throw new RedirectException(new URL('?job=' . $job->group()));
} else {
    echo $form;
}
