# Laravel Request ID

A request ID for every web request, command and job, and a small block for your error pages that shows it to the person who hits the error.

- **One ID per request, command or job.** Format: server code, project code, a hyphen and 10 random characters, for example `SRVAPP-K7Q2M9X4TB`.
- **`X-Request-Id` response header** on every response. An ID sent by the client is ignored.
- **In every log line** through Laravel's `Context`, and inherited by the jobs a request queues.
- **A tag on your Sentry (or GlitchTip) events**, when `sentry/sentry-laravel` is installed.
- **An `AccessDenied` event** on every 403 response, so your app can log it its own way.
- **A detail block** (ID, time, path, and rows your app adds) that you drop into your own error pages, with a copy button.
- **Starter error pages** for an app that has none.

## Requirements

- PHP 8.5
- Laravel 13

## Installation

The package is not on Packagist. Add the repository to `composer.json`, then require it:

```json
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:Rozkalns/laravel-request-id.git"
    }
]
```

```bash
composer require rozkalns/laravel-request-id
```

The package auto-discovers. Its middleware is added first in the global stack by the service provider: there is nothing to register in `bootstrap/app.php`.

## Configuration

Set the server code in `.env`. It is the same for every app on one server:

```dotenv
APP_SERVER_CODE=SRV
```

The project code belongs to the app, not to the environment. Publish the config file and write it there:

```bash
php artisan vendor:publish --tag=request-id-config
```

```php
// config/request-id.php
return [
    'server_code' => env('APP_SERVER_CODE', 'XXX'),
    'project_code' => 'APP',
    'timezone' => null,            // null: app.timezone
    'time_format' => 'd.m.Y H:i:s',
];
```

`XXX` is the reserved value for "not set". The `APP_PROJECT_CODE` env name works too, if you prefer it.

## Using the ID

```php
use Rozkalns\RequestId\RequestId;

RequestId::current(); // 'SRVAPP-K7Q2M9X4TB', or null when there is none
RequestId::assign();  // a new ID
RequestId::ensure();  // keep the inherited ID, or assign one
```

Commands and queued jobs get an ID too: a command assigns one when it starts, and a job keeps the ID of the request that queued it.

## Extension point 1: the `AccessDenied` event

The package writes no log line. It dispatches `Rozkalns\RequestId\Events\AccessDenied` once for every 403 response, and for no other status. Listen to it to log what your app knows:

```php
use Illuminate\Support\Facades\Log;
use Rozkalns\RequestId\Events\AccessDenied;

final class LogAccessDenied
{
    public function handle(AccessDenied $event): void
    {
        Log::notice('Access denied', ['route' => $event->request->route()?->getName()]);
    }
}
```

Laravel finds the listener by its `handle` type hint. Keep personal data out of the line.

## Extension point 2: rows in the block

The block shows the ID, the time and the path. To add more, such as who is signed in, bind your own `ErrorPageViewer`. It returns `label => value`, with the labels already translated:

```php
use Illuminate\Http\Request;
use Rozkalns\RequestId\Contracts\ErrorPageViewer;

final class AppErrorPageViewer implements ErrorPageViewer
{
    public function rows(Request $request): array
    {
        $user = $request->user();

        return $user === null ? [] : [__('errors.viewer') => $user->name];
    }
}
```

```php
// AppServiceProvider::register()
$this->app->bind(ErrorPageViewer::class, AppErrorPageViewer::class);
```

If your viewer throws, the block falls back to the ID, the time and the path. An error page never fails because of it.

## Adding the block to your own error pages

One line in your error layout, or in each page:

```blade
<x-request-id::details />
```

It renders nothing when the request has no ID, such as a pre-rendered maintenance page. The wrapper classes are a prop. The default is `mt-8 w-full max-w-sm border-t border-zinc-200 pt-4 dark:border-zinc-700`:

```blade
<x-request-id::details class="mt-6 text-sm" />
```

### The button slot

The default copy button is a plain `<button>`. To use your own, pass it in the `button` slot. Keep two data attributes: `data-error-copy` on the button and `data-copy-label` on the element whose text changes to "Copied":

```blade
<x-request-id::details>
    <x-slot:button>
        <flux:button size="sm" variant="ghost" data-error-copy>
            <span data-copy-label>{{ __('request-id::errors.copy') }}</span>
        </flux:button>
    </x-slot:button>
</x-request-id::details>
```

The labels come from `request-id::errors` (`request_id`, `time`, `path`, `copy`, `copied`), in Latvian and English.

## Starter pages

An app with no error pages can publish plain ones: a layout and the 403, 404, 419, 500 and 503 pages into `resources/views/errors/`.

```bash
php artisan vendor:publish --tag=request-id-pages
```

The layout is plain HTML with one inline style block. It follows the browser's light or dark setting, loads no script, font or image from anywhere, and already includes the block. After publishing, the files are yours. The pages never print the exception or its message.

## Tests

The package tests its own block and starter pages, including that they show no exception message, class name, stack trace or base path. **Each app keeps its own no-leak test for the pages it serves.** The package cannot see your pages.

```bash
composer test
```
