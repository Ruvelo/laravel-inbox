<?php

declare(strict_types=1);

/*
 * Builds the read-only demo published at https://ruvelo.github.io/laravel-inbox/.
 *
 * Seeds Halyard, a made-up billing platform, in an in-memory database, sends
 * Maya Okafor a fortnight of ordinary Laravel notifications, then renders a
 * page of the app (with the bell in its header), the inbox and the
 * preferences through the package's real routes and views, and writes the
 * HTML out as static files. In the browser, everything that would POST works
 * on the page only.
 *
 *   php demo/build.php <site-dir> [<shots-dir>]
 *
 * <shots-dir>, when given, receives the pages the screenshots are taken of:
 * without the demo banner, and with the bell's dropdown open.
 */

use App\Notifications\CommentMention;
use App\Notifications\ExportReady;
use App\Notifications\InvoicePaid;
use App\Notifications\NewSignIn;
use App\Notifications\PaymentFailed;
use App\Notifications\PayoutSent;
use App\Notifications\SubscriptionUpgraded;
use App\Notifications\TeammateJoined;
use App\Notifications\TrialsEnding;
use App\Notifications\WebhookFailing;
use Carbon\CarbonInterval;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Orchestra\Testbench\Foundation\Application;
use Ruvelo\Inbox\Inbox;
use Ruvelo\Inbox\InboxServiceProvider;
use Ruvelo\Inbox\Models\Notification;

require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/notifications.php';

const HOST = 'https://ruvelo.github.io';
const PATH = 'laravel-inbox';
const REPO = 'https://github.com/Ruvelo/laravel-inbox';

$site = rtrim($argv[1] ?? __DIR__.'/../build/site', '/');
$shots = isset($argv[2]) ? rtrim($argv[2], '/') : null;

foreach ([
    'APP_ENV' => 'testing',
    'APP_NAME' => 'Halyard',
    'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'APP_URL' => HOST,
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'SESSION_DRIVER' => 'array',
    'INBOX_PATH' => PATH.'/inbox',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

class DemoUser extends User
{
    use Notifiable;

    protected $table = 'users';

    protected $guarded = [];
}

$app = Application::create(basePath: null, options: ['extra' => ['providers' => [InboxServiceProvider::class]]]);
$app['config']->set('auth.providers.users.model', DemoUser::class);
$app['config']->set('inbox.per_page', 50);

Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('type');
    $table->morphs('notifiable');
    $table->text('data');
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
$app->make(ConsoleKernel::class)->call('migrate', ['--force' => true]);

// What people can switch on and off: normally in a service provider.
Inbox::preferences([
    InvoicePaid::class => ['group' => 'Billing', 'label' => 'Invoice paid', 'description' => 'When a customer pays one of your invoices.', 'defaults' => ['mail' => false]],
    PaymentFailed::class => ['group' => 'Billing', 'label' => 'Payment failed', 'description' => 'When a card is declined or a direct debit bounces.'],
    PayoutSent::class => ['group' => 'Billing', 'label' => 'Payouts', 'description' => 'When money leaves Halyard for your bank account.'],
    SubscriptionUpgraded::class => ['group' => 'Billing', 'label' => 'Plan changes', 'description' => 'Upgrades, downgrades and cancellations.', 'defaults' => ['mail' => false]],
    TrialsEnding::class => ['group' => 'Billing', 'label' => 'Trials ending', 'description' => 'A weekly heads-up about trials that end soon.', 'channels' => ['mail']],
    CommentMention::class => ['group' => 'Team', 'label' => 'Mentions and replies', 'description' => 'When someone mentions you or replies to your comment.'],
    TeammateJoined::class => ['group' => 'Team', 'label' => 'New teammates', 'description' => 'When someone accepts an invite to your workspace.', 'channels' => ['database']],
    ExportReady::class => ['group' => 'Reports', 'label' => 'Exports ready', 'description' => 'When a CSV or PDF you asked for is ready to download.'],
    WebhookFailing::class => ['group' => 'Developers', 'label' => 'Failing webhooks', 'description' => 'When an endpoint keeps returning errors.', 'channels' => ['mail', 'database', 'slack'], 'defaults' => ['slack' => false]],
    NewSignIn::class => ['group' => 'Security', 'label' => 'Security alerts', 'description' => 'New sign-ins and password changes. These keep your account safe.', 'required' => true],
]);

// --- Seed -------------------------------------------------------------------

$people = [];
foreach (['Maya Okafor', 'Tom Reyes', 'Inès Laurent', 'Kenji Mori'] as $name) {
    $first = strtok($name, ' ');
    $people[$first] = DemoUser::forceCreate([
        'name' => $name,
        'email' => strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $first)).'@halyard.test',
        'password' => bin2hex(random_bytes(16)),
    ]);
}
$maya = $people['Maya'];

$now = Carbon::now();
foreach (array_reverse(require __DIR__.'/content.php') as [$ago, $notification, $read]) {
    Carbon::setTestNow($now->copy()->sub(CarbonInterval::fromString($ago)));
    $notification->id = (string) Str::uuid();
    $maya->notify($notification);
    if ($read) {
        Inbox::markRead(Notification::query()->findOrFail($notification->id));
    }
}
Carbon::setTestNow();

// Maya has made a couple of choices already.
Inbox::updatePreferences($maya, [
    PaymentFailed::class => ['mail' => true],
    SubscriptionUpgraded::class => ['mail' => true],
    ExportReady::class => ['mail' => false],
]);

// A page of the host app, with the bell in its header.
View::addNamespace('demo', __DIR__.'/views');
Route::middleware('web')->get(PATH, fn () => view('demo::halyard'));

// --- Render -----------------------------------------------------------------

$http = $app->make(HttpKernel::class);

$fetch = function (string $path) use ($app, $http, $maya): string {
    $app['auth']->forgetGuards();
    $app['auth']->guard()->setUser($maya);

    $request = Request::create(HOST.'/'.PATH.($path === '' ? '' : '/'.$path));
    $response = $http->handle($request);
    $http->terminate($request, $response);

    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException("/{$path} answered {$response->getStatusCode()}");
    }

    // Root-relative links, so the same files work on Pages and on localhost.
    $html = str_replace(HOST.'/', '/', (string) $response->getContent());

    // The bell works on the page only: no polling, nothing posted.
    return str_replace('data-inbox-bell'."\n", 'data-inbox-bell data-inbox-demo'."\n", $html);
};

$banner = '<div style="background:#3d4eff;color:#fff;font:500 .875rem/1.4 \'Geist\',ui-sans-serif,system-ui,sans-serif;padding:.55rem 16px;text-align:center">'
    .'You’re looking at a read-only demo of <a href="'.REPO.'" style="color:inherit;font-weight:700">ruvelo/laravel-inbox</a>. '
    .'Clicks work on the page; nothing is saved. '
    .'<a href="'.REPO.'#readme" style="color:inherit">Read the docs</a></div>';

$demoScript = '<script>'.file_get_contents(__DIR__.'/demo.js').'</script>';

$write = function (string $root, string $path, string $html, bool $withBanner = true) use ($banner): void {
    if ($withBanner) {
        $html = (string) preg_replace('/<body[^>]*>/', '$0'.$banner, $html, 1);
    }
    $file = $root.'/'.($path === '' ? '' : $path.'/').'index.html';
    is_dir(dirname($file)) || mkdir(dirname($file), 0777, true);
    file_put_contents($file, $html);
};

$pages = [
    '' => $fetch(''),
    'inbox' => str_replace('</body>', $demoScript.'</body>', $fetch('inbox')),
    'inbox/preferences' => str_replace('</body>', $demoScript.'</body>', $fetch('inbox/preferences')),
];

foreach ($pages as $path => $html) {
    $write($site, $path, $html);
}

// Without JavaScript, items link to /inbox/{id}/open; in the demo that leads back to the inbox.
foreach (Notification::query()->pluck('id') as $id) {
    $write($site, "inbox/{$id}/open", '<!doctype html><meta charset="utf-8"><meta http-equiv="refresh" content="0; url=/'.PATH.'/inbox/"><title>Opening…</title><a href="/'.PATH.'/inbox/">Back to the inbox</a>', false);
}

if ($shots !== null) {
    // The bell, open, as if just clicked.
    $open = fn (string $html): string => str_replace('</body>', '<script>document.addEventListener("DOMContentLoaded",()=>{const b=document.querySelector("[data-inbox-toggle]");b.setAttribute("aria-expanded","true")})</script></body>',
        (string) preg_replace('/(data-inbox-panel[^>]*?)\s+hidden>/', '$1>', $html, 1));

    foreach ([
        'bell' => $open($pages['']),
        'inbox' => $pages['inbox'],
        'preferences' => $pages['inbox/preferences'],
    ] as $name => $html) {
        $write($shots, $name, $html, false);
    }
}

fwrite(STDOUT, sprintf("Built the demo (%d notifications, %d unread) into %s\n", Notification::query()->count(), Inbox::unreadCount($maya), $site));
