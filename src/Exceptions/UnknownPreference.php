<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Exceptions;

/**
 * A preference was set for a notification type or channel that isn't
 * registered with Inbox::preferences() or the `inbox.preferences` config.
 */
final class UnknownPreference extends InboxException
{
    public static function type(string $type): self
    {
        return new self("No notification type [{$type}] is registered for preferences.");
    }

    public static function channel(string $type, string $channel): self
    {
        return new self("The [{$channel}] channel can't be toggled for [{$type}].");
    }
}
