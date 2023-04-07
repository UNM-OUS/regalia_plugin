<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(400);
}

Context::response()->filename('response.json');

$fields = [];
// exact label matches
$query = Regalia::fields();
if ($phrase = trim(Context::arg('query'))) {
    $query->where('regalia_field.label = ?', $phrase);
}
while ($r = $query->fetch()) $fields[$r['id']] = $r;
// exact internal matches
$query = Regalia::fields();
if ($phrase = trim(Context::arg('query'))) {
    $query->where('regalia_field.label like ?', "%$phrase%");
}
$query->limit(20);
while ($r = $query->fetch()) $fields[$r['id']] = $r;
// fuzzier matches
$query = Regalia::fields();
foreach (explode(' ', Context::arg('query')) as $word) {
    $word = strtolower(trim($word));
    if ($word) {
        $query->where('regalia_field.label like ?', "%$word%");
    }
}
$query->limit(10);
while ($r = $query->fetch()) $fields[$r['id']] = $r;

echo json_encode(
    array_map(
        function (array $field) {
            return [
                'html' => sprintf('<div class="label">%s</div>', $field['label']),
                'value' => $field['id']
            ];
        },
        array_values($fields)
    )
);
