<?php

declare(strict_types=1);

use App\Notifications;

// Maya Okafor's inbox at Halyard, a B2B billing platform. Newest first:
// [how long ago, notification, read?].

return [
    ['12 minutes', new Notifications\CommentMention([
        'title' => 'Tom Reyes mentioned you on INV-2291',
        'body' => '@Maya can we waive the late fee for Northwind Freight? They paid the same morning the reminder went out.',
        'url' => '/invoices/INV-2291#comments',
        'actor' => 'Tom Reyes',
    ]), false],
    ['48 minutes', new Notifications\InvoicePaid([
        'title' => 'Invoice INV-2288 paid',
        'body' => 'Bluefin Studio paid €4,800.00 by SEPA transfer.',
        'url' => '/invoices/INV-2288',
        'icon' => '€',
    ]), false],
    ['2 hours', new Notifications\PaymentFailed([
        'title' => 'Payment failed for Lumen Health',
        'body' => 'Their Visa ending 4242 was declined (insufficient funds). We’ll retry automatically on 10 October.',
        'url' => '/customers/lumen-health/payments',
        'icon' => '⚠',
    ]), false],
    ['3 hours', new Notifications\ExportReady([
        'title' => 'Your September revenue export is ready',
        'body' => 'invoices-2026-09.csv, 1,204 rows. The download link works for 7 days.',
        'url' => '/reports/exports/9081',
        'icon' => '↓',
    ]), false],
    ['5 hours', new Notifications\TeammateJoined('Inès Laurent', 'Billing admin'), true],
    ['1 day 2 hours', new Notifications\CommentMention([
        'title' => 'Kenji Mori replied to your comment',
        'body' => 'Done: the VAT number is updated on the Corvid Labs account, so October’s invoice will show it.',
        'url' => '/customers/corvid-labs#comments',
        'actor' => 'Kenji Mori',
    ]), false],
    ['1 day 4 hours', new Notifications\PayoutSent([
        'title' => 'Payout of €31,940.12 sent',
        'body' => 'Arriving in your account ending 0198 within 2 business days.',
        'url' => '/payouts/po_8812',
        'icon' => '↗',
    ]), true],
    ['1 day 7 hours', new Notifications\InvoicePaid([
        'title' => 'Invoice INV-2284 paid',
        'body' => 'Acme Corp paid €2,400.00 by card.',
        'url' => '/invoices/INV-2284',
        'icon' => '€',
    ]), true],
    ['2 days 3 hours', new Notifications\SubscriptionUpgraded([
        'title' => 'Northwind Freight upgraded to Scale',
        'body' => '+€1,150 MRR, starting with their next invoice on 1 November.',
        'url' => '/customers/northwind-freight',
        'icon' => '↑',
    ]), true],
    ['2 days 6 hours', new Notifications\WebhookFailing([
        'title' => 'A webhook endpoint is failing',
        'body' => 'https://hooks.corvid.dev/halyard returned 500 for 18 deliveries in a row. We’ll keep retrying for 3 days.',
        'url' => '/developers/webhooks/we_221',
        'icon' => '{ }',
    ]), true],
    ['3 days 5 hours', new Notifications\TrialsEnding([
        'title' => '3 trials end this week',
        'body' => 'Pinecone Analytics, Mosaic and Tern Labs haven’t added a payment method yet.',
        'url' => '/customers?trial=ending',
        'icon' => '⌛',
    ]), true],
    ['4 days 1 hour', new Notifications\PaymentFailed([
        'title' => 'Payment failed for Mosaic',
        'body' => 'Their Mastercard ending 5100 has expired. We emailed them a link to update it.',
        'url' => '/customers/mosaic/payments',
        'icon' => '⚠',
    ]), true],
    ['6 days 2 hours', new Notifications\NewSignIn([
        'title' => 'New sign-in from Lisbon',
        'body' => 'Chrome on macOS. If this wasn’t you, reset your password and sign out other sessions.',
        'url' => '/settings/security',
        'icon' => '!',
    ]), true],
    ['9 days', new Notifications\ExportReady([
        'title' => 'Your Q3 VAT report is ready',
        'body' => 'vat-2026-q3.pdf, covering 3,611 invoices across 4 countries.',
        'url' => '/reports/exports/9012',
        'icon' => '↓',
    ]), true],
    ['12 days', new Notifications\TeammateJoined('Kenji Mori', 'Developer'), true],
];
