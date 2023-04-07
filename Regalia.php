<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DateTime;
use DigraphCMS\Datastore\Datastore;
use DigraphCMS\Plugins\AbstractPlugin;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\UserMenu;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use Envms\FluentPDO\Queries\Select;
use DigraphCMS_Plugins\unmous\ous_digraph_module\SharedDB;
use DigraphCMS_Plugins\unmous\regalia\Requests\RegaliaRequests;
use Envms\FluentPDO\Query;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Regalia extends AbstractPlugin
{
    const EDITOR_ACTIONS = [
        '_add_order_group',
        '_add_extras',
        '_edit_order_group',
        'extras:',
        'order:',
        'requests'
    ];

    public static function onShortCode_regalia_deadline(ShortcodeInterface $s): string
    {
        if (!static::orderDeadline()) return '[deadline TBD]';
        if ($s->getParameter('time', 'false') !== 'false') {
            return Format::datetime(static::orderDeadline());
        } else {
            return Format::date(static::orderDeadline());
        }
    }

    public static function onShortCode_regalia_cancellation_deadline(ShortcodeInterface $s): string
    {
        if (!static::cancellationDeadline()) return '[deadline TBD]';
        if ($s->getParameter('time', 'false') !== 'false') {
            return Format::datetime(static::cancellationDeadline());
        } else {
            return Format::date(static::cancellationDeadline());
        }
    }

    public static function onStaticUrlPermissions_regalia(URL $url)
    {
        if ($url->route() == 'regalia/global_regalia_settings') return Permissions::inMetaGroup('regalia__admin');
        elseif (in_array($url->action(), static::EDITOR_ACTIONS)) return Permissions::inMetaGroup('regalia__edit');
        elseif (in_array($url->actionPrefix() . ':', static::EDITOR_ACTIONS)) return Permissions::inMetaGroup('regalia__edit');
        else return Permissions::inMetaGroup('regalia__view');
    }

    public static function orderDeadline(Semester $semester = null): ?DateTime
    {
        $semester = $semester ?? Semesters::current();
        if ($time = intval(Datastore::value('regalia', 'order-deadline', strval($semester->intVal())))) {
            return (new DateTime)->setTimestamp($time);
        } else return null;
    }

    public static function cancellationDeadline(Semester $semester = null): ?DateTime
    {
        $semester = $semester ?? Semesters::current();
        if ($time = intval(Datastore::value('regalia', 'cancellation-deadline', strval($semester->intVal())))) {
            return (new DateTime)->setTimestamp($time);
        } else return null;
    }

    public static function cronJob_halfhourly()
    {
        // loop through order groups
        foreach (RegaliaGroups::getBySemester(Semesters::current()) as $group) {
            /**
             * Cancel/uncancel orders and requests as needed to maintain consistency
             * Uncancels orders for which there is an uncancelled request
             * Cancels orders for which there are no uncancelled requests
             */
            if (!$group->cancellationLocked()) {
                // loop through orders
                $orders = $group->orders();
                while ($order = $orders->fetch()) {
                    // check that there are requests
                    $requests = RegaliaRequests::select()->where('assigned_order', $order->id());
                    if (!$requests->count()) continue;
                    // if order is cancelled, look for an uncancelled request
                    // and uncancel the order if one is found
                    if ($order->cancelled()) {
                        while ($request = $requests->fetch()) {
                            if (!$request->cancelled()) {
                                $order->setCancelled(false)->save();
                                break;
                            }
                        }
                    }
                    // if order is not cancelled, check if all requests are cancelled
                    // and cancel order if they are
                    else {
                        $allCancelled = true;
                        while ($request = $requests->fetch()) {
                            if (!$request->cancelled()) $allCancelled = false;
                        }
                        if ($allCancelled) {
                            $order->setCancelled(true)->save();
                        }
                    }
                    // look for tam preferences in requests and set order's preference accordingly
                    $requests = RegaliaRequests::select()->where('assigned_order', $order->id())->nonCancelled();
                    $order->setTam(null);
                    while ($request = $requests->fetch()) {
                        if ($request->parent()->regaliaPrefersTam()) $order->setTam(true);
                        break;
                    }
                    $order->save();
                }
            }
        }
    }

    public static function query(): Query
    {
        return SharedDB::query();
    }

    public static function validatePersonInfo(?string $for, bool $allowNotFound = false): bool
    {
        // some info should be saved
        if (!$for) return false;
        if (!($person = static::getFullPersonInfo($for))) return false;
        // a part is needed
        if ($person['needs_hat'] != 1 && $person['needs_robe'] != 1 && $person['needs_hood'] != 1) return false;
        // hat validation
        if ($person['needs_hat'] == 1) {
            if (!$person['size_hat']) return false;
        }
        // robe validation
        if ($person['needs_robe'] == 1) {
            if (!$person['size_height']) return false;
            if (!$person['size_weight']) return false;
            if (!$person['size_gender']) return false;
            if (!$person['degree_level']) return false;
        }
        // hood validation
        if ($person['needs_hood'] == 1) {
            if (!$person['degree_level']) return false;
            if (!$person['degree_field']) return false;
            if ($person['jostens_field_deprecated']) return false;
            if ($person['jostens_institution_deprecated']) return false;
            if ($person['preset_deprecated']) return false;
            if ($person['field_deprecated']) return false;
            if ($person['institution_deprecated']) return false;
            // institution validation
            if (!$allowNotFound) {
                if (!$person['institution_id']) return false;
            } else {
                if (!$person['institution_id'] && !$person['institution_notfound']) return false;
            }
        }
        // return true if nothing is wrong
        return true;
    }

    public static function getPersonInfo(?string $for)
    {
        if ($for === null) return null;
        return static::people()
            ->where('identifier = ?', [$for])
            ->fetch();
    }

    public static function getFullPersonInfo(string $for)
    {
        return static::people()
            ->where('identifier', $for)
            ->leftJoin('regalia_preset on regalia_preset.id = regalia_person.preset_id')
            ->leftJoin('regalia_field on regalia_field.id = regalia_person.field_id')
            ->leftJoin('jostens_field on jostens_field.id = regalia_field.jostens_id')
            ->leftJoin('regalia_institution on regalia_institution.id = regalia_person.institution_id')
            ->leftJoin('jostens_institution on jostens_institution.id = regalia_institution.jostens_id')
            ->select('regalia_person.*', true)
            // joined data
            ->select('regalia_preset.level as degree_level')
            ->select('jostens_field.field_name as degree_field')
            ->select('jostens_institution.institution_name as inst_name')
            ->select('jostens_institution.institution_city as inst_city')
            ->select('jostens_institution.institution_state as inst_state')
            ->select('jostens_field.field_color as color_band')
            ->select('jostens_institution.institution_color_lining1 as color_lining')
            ->select('jostens_institution.institution_color_chevron1 as color_chevron')
            // deprecation fields
            ->select('jostens_field.field_deprecated as jostens_field_deprecated')
            ->select('jostens_institution.institution_deprecated as jostens_institution_deprecated')
            ->select('regalia_preset.deprecated as preset_deprecated')
            ->select('regalia_field.deprecated as field_deprecated')
            ->select('regalia_institution.deprecated as institution_deprecated')
            // fetch
            ->fetch();
    }

    public static function people(): Select
    {
        return SharedDB::query()
            ->from('regalia_person');
    }

    public static function institution(?int $id)
    {
        if ($id === null) return null;
        return static::allInstitutions()
            ->where('regalia_institution.id = ?', [$id])
            ->fetch();
    }

    public static function preset(?int $id)
    {
        if ($id === null) return null;
        return static::allPresets()
            ->where('regalia_preset.id = ?', [$id])
            ->fetch();
    }

    public static function field(?int $id)
    {
        if ($id === null) return null;
        $field = static::fields()
            ->where('regalia_field.id = ?', [$id])
            ->fetch();
        if (!$field) return null;
        return $field;
    }

    public static function allFields(): Select
    {
        return SharedDB::query()
            ->from('regalia_field')
            ->leftJoin('jostens_field ON regalia_field.jostens_id = jostens_field.id')
            ->select('jostens_field.*')
            ->select('regalia_field.id as id');
    }

    public static function fields(): Select
    {
        return static::allFields()
            ->where('(field_deprecated <> 1 AND deprecated <> 1)');
    }

    public static function allInstitutions(): Select
    {
        return SharedDB::query()
            ->from('regalia_institution')
            ->leftJoin('jostens_institution ON regalia_institution.jostens_id = jostens_institution.id')
            ->select('regalia_institution.id as id')
            ->select('jostens_institution.institution_name as jostens_name')
            ->select('jostens_institution.institution_city as jostens_city')
            ->select('jostens_institution.institution_state as jostens_state')
            ->select('jostens_institution.institution_color_lining1 as color_lining')
            ->select('jostens_institution.institution_color_chevron1 as color_chevron')
            ->select('jostens_institution.institution_deprecated as institution_deprecated');
    }

    public static function allPresets(): Select
    {
        return SharedDB::query()
            ->from('regalia_preset')
            ->leftJoin('regalia_field on regalia_preset.field = regalia_field.id')
            ->leftJoin('jostens_field on regalia_field.jostens_id = jostens_field.id')
            ->select('regalia_preset.id as id')
            ->select('regalia_preset.label as label')
            ->select('regalia_field.id as field_id')
            ->select('regalia_field.label as field_label')
            ->select('regalia_field.deprecated as field_deprecated')
            ->select('jostens_field.field_name as jostens_name')
            ->select('jostens_field.id as jostens_id')
            ->select('jostens_field.field_deprecated as jostens_deprecated')
            ->order('weight ASC, regalia_preset.label ASC');
    }

    public static function presets(): Select
    {
        return static::allPresets()
            ->where('regalia_preset.deprecated <> 1')
            ->where('(regalia_field.deprecated IS NULL OR regalia_field.deprecated <> 1)')
            ->where('(jostens_field.field_deprecated IS NULL OR jostens_field.field_deprecated <> 1)');
    }

    public static function institutions(): Select
    {
        return static::allInstitutions()
            ->where('(institution_deprecated <> 1 AND deprecated <> 1)');
    }

    public static function onUserMenu_user(UserMenu $menu)
    {
        $menu->addURL(new URL('/regalia/'));
    }
}
