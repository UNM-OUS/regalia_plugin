<?php

use DigraphCMS\Context;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Session\Cookies;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

if (Context::arg('csrf') !== Cookies::csrfToken('autocomplete')) {
    throw new HttpError(400);
}

Context::response()->filename('response.json');

$q = Context::arg('query');

$institutions = [];
// special match just for UNM
if (strtolower(trim($q)) == 'unm') {
    $query = Regalia::institutions()
        ->where('regalia_institution.label like ?', "%University of New Mexico%")
        ->limit(10);
    while ($r = $query->fetch()) $institutions[$r['id']] = $r;
}
// exact phrase matches
$query = Regalia::institutions();
if ($phrase = trim($q)) {
    $query->where('regalia_institution.label like ?', "%$phrase%");
}
$query->limit(20);
while ($r = $query->fetch()) $institutions[$r['id']] = $r;
// fuzzier matches
$query = Regalia::institutions();
foreach (explode(' ', $q) as $word) {
    $word = strtolower(trim($word));
    if ($word) {
        $query->where('regalia_institution.label like ?', "%$word%");
    }
}
$query->limit(20);
while ($r = $query->fetch()) $institutions[$r['id']] = $r;

echo json_encode(
    array_map(
        function (array $institution) {
            return [
                'html' => sprintf('<div class="label">%s</div>', $institution['label']),
                'value' => $institution['id']
            ];
        },
        array_values($institutions)
    )
);
