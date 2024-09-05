<h1>Add extras</h1>
<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroups;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;

$group = RegaliaGroups::get(Context::arg('id'));
if (!$group) throw new HttpError(404);
Breadcrumb::parent($group->url());

$sizes = [
    [
        'Extra-Extra-small: 5\' 0"',
        [$group, true, true, true, 60, 120, 'M'] // arguments for RegaliaOrder::createExtra()
    ],
    [
        'Extra-Small: 5\' 4"',
        [$group, true, true, true, 64, 130, 'M'] // arguments for RegaliaOrder::createExtra()
    ],
    [
        'Small: 5\' 6"',
        [$group, true, true, true, 66, 149, 'L'] // arguments for RegaliaOrder::createExtra()
    ],
    [
        'Medium: 5\' 10"',
        [$group, true, true, true, 70, 180, 'L'] // arguments for RegaliaOrder::createExtra()
    ],
    [
        'Large: 6\' 0"',
        [$group, true, true, true, 72, 200, 'XL'] // arguments for RegaliaOrder::createExtra()
    ],
    [
        'Extra-Large: 6\' 2"',
        [$group, true, true, true, 74, 220, 'XL'] // arguments for RegaliaOrder::createExtra()
    ]
];

if ($group->tams()) {
    $sizes = array_merge($sizes, [
        [
            'Hat only: Extra-Small',
            [$group, true, false, false, null, null, 'XS'] // arguments for RegaliaOrder::createExtra()
        ],
        [
            'Hat only: Small',
            [$group, true, false, false, null, null, 'S'] // arguments for RegaliaOrder::createExtra()
        ],
        [
            'Hat only: Medium',
            [$group, true, false, false, null, null, 'M'] // arguments for RegaliaOrder::createExtra()
        ],
        [
            'Hat only: Large',
            [$group, true, false, false, null, null, 'L'] // arguments for RegaliaOrder::createExtra()
        ],
        [
            'Hat only: Extra-Large',
            [$group, true, false, false, null, null, 'XL'] // arguments for RegaliaOrder::createExtra()
        ]
    ]);
}

$form = new FormWrapper();

foreach ($sizes as list($label, $args)) {
    // set up field
    $input = (new Field($label))
        ->addForm($form);
    $input->input()->setAttribute('type', 'number');
    // callback to actually add them
    $form->addCallback(function () use ($input, $args) {
        if (!$input->value()) return;
        for ($i = 0; $i < $input->value(); $i++) {
            call_user_func_array(
                [RegaliaOrder::class, 'createExtra'],
                $args
            );
        }
    });
}

$form->addCallback(function () use ($group) {
    throw new RedirectException($group->url());
});

echo $form;
