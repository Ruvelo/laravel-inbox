<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Exceptions;

/**
 * A preference type was registered with a missing or malformed definition.
 */
final class InvalidPreferenceType extends InboxException
{
    public static function because(string $type, string $reason): self
    {
        return new self("Preference type [{$type}] is invalid: {$reason}");
    }
}
