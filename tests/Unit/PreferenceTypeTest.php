<?php

declare(strict_types=1);

namespace Ruvelo\Inbox\Tests\Unit;

use Ruvelo\Inbox\Exceptions\InvalidPreferenceType;
use Ruvelo\Inbox\PreferenceType;
use Ruvelo\Inbox\Tests\TestCase;

class PreferenceTypeTest extends TestCase
{
    public function test_from_a_full_definition(): void
    {
        $type = PreferenceType::fromConfig('App\\Notifications\\InvoicePaid', [
            'label' => 'Invoice paid',
            'description' => 'When a customer pays.',
            'group' => 'Billing',
            'channels' => ['mail', 'database'],
            'defaults' => ['mail' => false],
        ]);

        $this->assertSame('Invoice paid', $type->label);
        $this->assertSame('When a customer pays.', $type->description);
        $this->assertSame('Billing', $type->group);
        $this->assertFalse($type->defaultFor('mail'));
        $this->assertTrue($type->defaultFor('database'));
        $this->assertTrue($type->allows('mail'));
        $this->assertFalse($type->allows('slack'));
        $this->assertFalse($type->required);
    }

    public function test_a_label_is_enough(): void
    {
        $type = PreferenceType::fromConfig('weekly-digest', 'Weekly digest');

        $this->assertSame('Weekly digest', $type->label);
        $this->assertSame(['mail', 'database'], $type->channels);
    }

    public function test_a_missing_label_is_humanised(): void
    {
        $this->assertSame('Payment failed', PreferenceType::fromConfig('App\\Notifications\\PaymentFailedNotification', [])->label);
    }

    public function test_the_id_is_form_safe_and_stable(): void
    {
        $id = PreferenceType::fromConfig('App\\Notifications\\InvoicePaid', [])->id();

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $id);
        $this->assertSame($id, PreferenceType::fromConfig('App\\Notifications\\InvoicePaid', 'Other label')->id());
        $this->assertNotSame($id, PreferenceType::fromConfig('App\\Notifications\\InvoicePaid2', [])->id());
    }

    public function test_bad_channels_are_refused(): void
    {
        $this->expectException(InvalidPreferenceType::class);

        PreferenceType::fromConfig('x', ['channels' => []]);
    }

    public function test_defaults_must_be_for_its_channels(): void
    {
        $this->expectException(InvalidPreferenceType::class);

        PreferenceType::fromConfig('x', ['channels' => ['mail'], 'defaults' => ['slack' => false]]);
    }
}
