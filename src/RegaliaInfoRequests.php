<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\Datastore\DatastoreGroup;
use DigraphCMS\Digraph;
use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\RichContent\RichContent;
use DigraphCMS\UI\Templates;
use DigraphCMS\URL\URL;

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
        $emails = [$identifier];
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

    protected static function datastore(): DatastoreGroup
    {
        static $group;
        return $group
            ?? $group = new DatastoreGroup('regalia', 'info_requests');
    }
}
