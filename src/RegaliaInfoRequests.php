<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\Digraph;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;

class RegaliaInfoRequests
{
    public static function exists(string $uuid): bool
    {
        return static::datastore()->exists($uuid);
    }

    public static function expired(string $uuid): null|bool
    {
        $item = static::datastore()->get($uuid);
        if (!$item) return null;
        return $item->created()->getTimestamp() < time() - (86400 * 7);
    }

    public static function identifier(string $uuid): null|string
    {
        $item = static::datastore()->get($uuid);
        if (!$item) return null;
        if ($item->created()->getTimestamp() < time() - (86400 * 7)) return null;
        return $item->value();
    }

    public static function notifyCreator(string $uuid): void
    {
        $item = static::datastore()->get($uuid);
        if (!$item) return;
        $email = $item->createdBy()->primaryEmail();
        if (!$email) return;
        $name = PersonInfo::getFullNameFor(static::identifier($uuid));
        if ($name) $name = "$name ($uuid)";
        else $name = $uuid;
        Emails::queue(
            new Email(
                'service',
                'Regalia info collected for ' . $name,
                $email,
                $item->createdByUUID(),
                null,
                Templates::render('/regalia/request_notification.php', ['name' => $name])
            )
        );
    }

    public static function url(string $uuid): URL
    {
        return new URL('/request_size/request:' . $uuid);
    }

    public static function create(string $identifier, string|null $additional_email)
    {
        $uuid = Digraph::uuid();
        static::datastore()->set(
            $uuid,
            $identifier,
            [
                'additional_email' => $additional_email
            ]
        );
        // send email
        $message = Templates::render('regalia/request_message.php', ['url' => static::url($uuid)]);
        $emails = [];
        if (str_contains('@', $identifier)) $emails[] = $identifier;
        else $emails[] = "$identifier@unm.edu";
        if ($additional_email) $emails[] = $additional_email;
        $emails = array_unique(array_map(strtolower(...), $emails));
        foreach ($emails as $email) {
            Emails::queue(Email::newForEmail(
                'regalia-info-request',
                $email,
                'Regalia information request',
                new RichContent($message)
            ));
        }
    }

    public static function datastore(): DatastoreGroup
    {
        static $group;
        return $group
            ?? $group = new DatastoreGroup('regalia', 'info_requests');
    }
}
