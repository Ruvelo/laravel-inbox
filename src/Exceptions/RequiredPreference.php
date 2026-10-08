<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Exceptions;

/**
 * Someone tried to turn off a notification type marked as required.
 */
final class RequiredPreference extends InboxException
{
    public static function type(string $type): self
    {
        return new self("Notifications of type [{$type}] are required and can't be turned off.");
    }
}
