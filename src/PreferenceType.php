<?php

declare(strict_types=1);

namespace Ruvelo\Inbox;

use Illuminate\Support\Str;
use Ruvelo\Inbox\Exceptions\InvalidPreferenceType;

/**
 * A notification type people can switch on and off, per channel.
 */
final readonly class PreferenceType
{
    /**
     * @param  list<string>  $channels  the channels the user may toggle
     * @param  array<string, bool>  $defaults  channel => on by default (missing = on)
     */
    public function __construct(
        public string $key,
        public string $label,
        public ?string $description = null,
        public array $channels = ['mail', 'database'],
        public array $defaults = [],
        public bool $required = false,
        public ?string $group = null,
    ) {}

    /**
     * @param  array<array-key, mixed>|string  $definition  a label, or the full definition
     *
     * @throws InvalidPreferenceType
     */
    public static function fromConfig(string $key, array|string $definition): self
    {
        if ($key === '') {
            throw InvalidPreferenceType::because($key, 'the key is empty.');
        }

        if (is_string($definition)) {
            $definition = ['label' => $definition];
        }

        $channels = $definition['channels'] ?? ['mail', 'database'];

        if (! is_array($channels) || $channels === [] || array_filter($channels, fn ($channel) => ! is_string($channel) || $channel === '') !== []) {
            throw InvalidPreferenceType::because($key, '`channels` must be a non-empty list of channel names.');
        }

        $defaults = [];
        foreach (is_array($definition['defaults'] ?? null) ? $definition['defaults'] : [] as $channel => $on) {
            if (! in_array($channel, $channels, true)) {
                throw InvalidPreferenceType::because($key, "the default for [{$channel}] isn't one of its channels.");
            }
            $defaults[(string) $channel] = (bool) $on;
        }

        $label = $definition['label'] ?? null;
        $description = $definition['description'] ?? null;
        $group = $definition['group'] ?? null;

        return new self(
            key: $key,
            label: is_string($label) && $label !== '' ? $label : Inbox::humanise($key),
            description: is_string($description) && $description !== '' ? $description : null,
            channels: array_values(array_unique(array_map(strval(...), $channels))),
            defaults: $defaults,
            required: (bool) ($definition['required'] ?? false),
            group: is_string($group) && $group !== '' ? $group : null,
        );
    }

    public function allows(string $channel): bool
    {
        return in_array($channel, $this->channels, true);
    }

    public function defaultFor(string $channel): bool
    {
        return $this->defaults[$channel] ?? true;
    }

    /**
     * A stable, form-safe id for this type (class names have backslashes).
     */
    public function id(): string
    {
        return Str::slug(str_replace('\\', '-', $this->key)).'-'.substr(hash('xxh3', $this->key), 0, 6);
    }
}
