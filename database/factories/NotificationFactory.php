<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Ruvelo\Inbox\InboxMessage;
use Ruvelo\Inbox\Models\Notification;

/**
 * For your own tests:
 *
 *     Notification::factory()->for($user, 'notifiable')->unread()->create();
 *     Notification::factory()->for($user, 'notifiable')->ofType(InvoicePaid::class)
 *         ->message(InboxMessage::make('Invoice paid'))->count(3)->create();
 *
 * @extends Factory<Notification>
 */
final class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\'.$this->faker->randomElement(['InvoicePaid', 'PaymentFailed', 'ExportReady', 'TeammateJoined']),
            'data' => [
                'title' => rtrim($this->faker->sentence(4), '.'),
                'body' => $this->faker->sentence(12),
                'url' => '/'.$this->faker->slug(2),
            ],
            'read_at' => null,
        ];
    }

    /**
     * Shorthand for ->for($notifiable, 'notifiable').
     */
    public function to(Model $notifiable): self
    {
        return $this->for($notifiable, 'notifiable');
    }

    public function unread(): self
    {
        return $this->state(['read_at' => null]);
    }

    public function read(?Carbon $at = null): self
    {
        return $this->state(fn () => ['read_at' => $at ?? Carbon::now()]);
    }

    public function ofType(string $type): self
    {
        return $this->state(['type' => $type]);
    }

    /**
     * @param  InboxMessage|array<array-key, mixed>  $data
     */
    public function message(InboxMessage|array $data): self
    {
        return $this->state(['data' => $data instanceof InboxMessage ? array_filter($data->toArray(), fn ($value) => $value !== null) : $data]);
    }

    public function sentAt(Carbon $at): self
    {
        return $this->state(['created_at' => $at, 'updated_at' => $at]);
    }
}
