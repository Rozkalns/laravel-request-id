<?php

declare(strict_types=1);

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;
use Monolog\LogRecord;
use Rozkalns\RequestId\RequestId;
use Sentry\Event;
use Sentry\State\Scope;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

use function Sentry\configureScope;

beforeEach(function (): void {
    config([
        'logging.default' => 'capture',
        'logging.channels.capture' => ['driver' => 'monolog', 'handler' => TestHandler::class],
    ]);

    Route::get('/probe', function (): string {
        Log::info('probe line');

        return 'ok';
    });
});

/** @return list<LogRecord> */
function loggedLines(string $message): array
{
    $handler = Log::channel('capture')->getLogger()->getHandlers()[0];
    $records = $handler instanceof TestHandler ? $handler->getRecords() : [];

    return array_values(array_filter($records, fn (LogRecord $record): bool => $record->message === $message));
}

it('puts the server and project codes before 10 characters from the Crockford alphabet', function (): void {
    config(['request-id.server_code' => 'SRV', 'request-id.project_code' => 'APP']);

    expect(RequestId::assign())->toMatch('/^SRVAPP-[0-9A-HJKMNP-TV-Z]{10}$/');
});

it('uses the reserved XXX codes when none is set', function (): void {
    expect(RequestId::assign())->toStartWith('XXXXXX-');
});

it('reads the codes from the environment names', function (): void {
    $config = require __DIR__.'/../config/request-id.php';

    expect($config['server_code'])->toBe('XXX')->and($config['project_code'])->toBe('XXX');
});

it('assigns a new ID each time and reads it back', function (): void {
    $first = RequestId::assign();
    $second = RequestId::assign();

    expect($first)->not->toBe($second)
        ->and(RequestId::current())->toBe($second)
        ->and(Context::get('request_id'))->toBe($second);
});

it('has no current ID before one is assigned', function (): void {
    Context::forget('request_id');

    expect(RequestId::current())->toBeNull();
});

it('ignores a context value that is not a string', function (): void {
    Context::add('request_id', 42);

    expect(RequestId::current())->toBeNull();
});

it('keeps an inherited ID on ensure and assigns one when there is none', function (): void {
    Context::add('request_id', 'SRVAPP-PARENT0000');

    expect(RequestId::ensure())->toBe('SRVAPP-PARENT0000');

    Context::forget('request_id');

    expect(RequestId::ensure())->toHaveLength(17)->and(RequestId::current())->toHaveLength(17);
});

it('tags the Sentry scope with the ID', function (): void {
    $id = RequestId::assign();
    $tags = [];

    configureScope(function (Scope $scope) use (&$tags): void {
        $event = $scope->applyToEvent(Event::createEvent());
        $tags = $event?->getTags() ?? [];
    });

    expect($tags)->toHaveKey('request_id', $id);
});

it('puts an ID on a 200 and a 404 response', function (): void {
    expect($this->get('/probe')->assertOk()->headers->get('X-Request-Id'))->toHaveLength(17)
        ->and($this->get('/no-such-page')->assertNotFound()->headers->get('X-Request-Id'))->toHaveLength(17);
});

it('gives two requests two different IDs', function (): void {
    $first = $this->get('/probe')->headers->get('X-Request-Id');
    $second = $this->get('/probe')->headers->get('X-Request-Id');

    expect($first)->not->toBe($second);
});

it('ignores an ID the client sends', function (): void {
    $response = $this->withHeaders(['X-Request-Id' => 'FORGED00AA'])->get('/probe');

    expect($response->headers->get('X-Request-Id'))->not->toBe('FORGED00AA')->toHaveLength(17);
});

it('stamps a log line written during the request with the header ID', function (): void {
    $id = $this->get('/probe')->headers->get('X-Request-Id');

    expect(loggedLines('probe line')[0]->extra['request_id'])->toBe($id);
});

it('gives an artisan command its own ID and keeps an inherited one', function (): void {
    event(new CommandStarting('request-id:probe', new ArrayInput([]), new NullOutput));
    Log::info('command line');

    expect(loggedLines('command line')[0]->extra['request_id'])->toHaveLength(17);

    Context::add('request_id', 'SRVAPP-PARENT0000');
    event(new CommandStarting('request-id:probe', new ArrayInput([]), new NullOutput));

    expect(RequestId::current())->toBe('SRVAPP-PARENT0000');
});

it('gives a job queued without an ID its own', function (): void {
    Context::flush();
    Context::hydrate(null);
    Log::info('job line');

    expect(loggedLines('job line')[0]->extra['request_id'])->toHaveLength(17);
});

it('stamps a job with the ID of the request that queued it', function (): void {
    Route::get('/queue', function (): string {
        dispatch(function (): void {
            Log::info('queued line');
        })->onConnection('sync');

        return 'queued';
    });

    $id = $this->get('/queue')->headers->get('X-Request-Id');
    $payload = Context::dehydrate();
    Context::flush();
    Context::hydrate($payload);

    expect(loggedLines('queued line')[0]->extra['request_id'])->toBe($id)
        ->and(RequestId::current())->toBe($id);
});
