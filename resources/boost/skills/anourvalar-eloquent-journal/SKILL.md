---
name: anourvalar-eloquent-journal
description: Load when working with the anourvalar/eloquent-journal Laravel package to log Eloquent model changes, external integration calls, or arbitrary metrics through AnourValar\EloquentJournal\Service, configure entities/types/events, or wire up the bundled Journal Web/Api controllers.
---

# AnourValar Eloquent Journal

`anourvalar/eloquent-journal` is a Laravel package that writes structured audit/log/metric records ("journals") to a single `journals` table. It supports three record types out of the box: `model` (Eloquent diffs captured via an Observer), `integration` (external HTTP/provider calls), and `metric` (arbitrary events). All entries are written through `AnourValar\EloquentJournal\Service`, validated against rules driven by the `eloquent_journal` config, and dispatched as `JournalCreated` events.

## When to use

- The user wants to install, configure, or extend `anourvalar/eloquent-journal`.
- You need to log Eloquent model create/update/delete/restore events with attribute diffs.
- You need to log a third-party API/integration call (with `AnourValar\HttpClient\Response` support) or arbitrary metric.
- The user is wiring up the bundled `Api\JournalController` or `Web\JournalController` routes, scheduling pruning, configuring morph map entries, or registering a custom `Handlers\TypeInterface`.
- The user references `App\Journal`, `Service::captureModel()`, `captureIntegration()`, `captureMetric()`, `IntegrationException`, or `JournalCreated`.

## Facades

This package ships no `Illuminate\Support\Facades\Facade` subclasses. All access goes through the container-resolved service classes documented below (typically via `App::make(...)`, constructor injection, or `app(Service::class)`).

## Services

### `AnourValar\EloquentJournal\Service`

The main entry point. Resolve via the container (`app(\AnourValar\EloquentJournal\Service::class)`). Each capture call validates input through the configured `Journal` model and dispatches `JournalCreated`.

Constructor: `__construct(?\Illuminate\Foundation\Auth\User $user = null, ?string $ipAddress = null)`

Public methods:

- `user(?\Illuminate\Foundation\Auth\User $user): self` — returns a clone with the "current" user overridden (otherwise `Auth::id()` is used).
- `ipAddress(?string $ipAddress): self` — returns a clone with the "current" IP overridden (otherwise `Request::ip()` is used; `127.0.0.1` is treated as null).
- `captureModel(string $event, Model $entity, ?array $dataExtra = null, bool $success = true, array|string|null $tags = null): ?Journal` — logs a model change. `$event` must be one of `create|update|delete|restore`. Returns `null` if the model has no real changes on an `update`. Requires the entity to use `AnourValar\EloquentValidation\ModelTrait` and to be registered both in `Relation::morphMap()` and in `config('eloquent_journal.entity')`.
- `captureIntegration(string $event, ?Model $entity = null, array|null|\AnourValar\HttpClient\Response $data = null, bool $success = true, array|string|null $tags = null): Journal` — logs an external/integration call. `$event` must NOT be one of the four model events. If `$data` is an `HttpClient\Response`, `$data->dump(true)` is stored.
- `captureMetric(string $event, ?Model $entity = null, ?array $data = null, bool $success = true, array|string|null $tags = null): Journal` — logs an arbitrary metric. `$event` must NOT be one of the four model events.
- `publishConfig(string $prefix = ''): array` — returns `['{prefix}entities' => [...], '{prefix}events' => [...]]` for front-end use. Non-admin users (`! can('admin.administration')`) only see events flagged `is_public => true`.

```php
use AnourValar\EloquentJournal\Service;

$journal = app(Service::class)->captureMetric(
    event: 'user_token_obtain',
    entity: $user,
    data: ['ua' => request()->userAgent()],
    tags: 'auth',
);
```

### `AnourValar\EloquentJournal\Journal` (Eloquent model)

Abstract base for the journal record. Consumers extend the published `App\Journal` stub. Uses `MassPrunable` (`prunable()` deletes rows older than 12 months). Notable members:

- `user(): BelongsTo`, `entitable(): MorphTo` (uses `withTrashed()`).
- `scopeHeavy(Builder)` — eager-loads user and selects all display columns plus virtual ones (`entity_title`, `type_title`, `event_title`, `short_description`, `full_description`).
- `scopeAcl(Builder, ?User = null)` — restricts non-admin users to their own rows and public events.
- `getTypeHandler(): Handlers\TypeInterface` — resolves the handler from `config('eloquent_journal.type.{type}.bind')`.
- Virtual attributes: `type_details`, `type_title`, `entity_details`, `entity_title`, `entity_class`, `event_details`, `event_title`, `short_description`, `full_description`.

### `AnourValar\EloquentJournal\Observer`

Generic model observer that calls `Service::captureModel('create'|'update'|'delete'|'restore', $model)` on `created`, `updated`, `deleted` lifecycle events when `Auth::id()` is set. The service provider auto-attaches it to every entity in `config('eloquent_journal.entity')` whose `observe` flag is true; you do not normally instantiate it yourself.

### `AnourValar\EloquentJournal\IntegrationException`

Reportable exception that defers a failed integration log. Throw it inside an HTTP integration to let Laravel's exception handler record the failure via `report()`.

- `__construct(string $event, \AnourValar\HttpClient\Response|array|null $response, ?Model $entity = null, array|string|null $tags = null)`
- `report(): void` — calls `Service::captureIntegration($event, $entity, $response, success: false, tags: $tags)`.

```php
use AnourValar\EloquentJournal\IntegrationException;

if (! $response->successful()) {
    throw new IntegrationException('sms_send', $response, $user, tags: 'sms');
}
```

### `AnourValar\EloquentJournal\Events\JournalCreated`

Dispatched after every successful `Service::create()`. Exposes a public `$journal` property of type `Journal`. Use it to fan out notifications, broadcast, etc.

### Handlers (`AnourValar\EloquentJournal\Handlers`)

- `TypeInterface` — contract every journal "type" must implement:
  - `validate(\Illuminate\Validation\Validator &$validator): void`
  - `shortDescription(Journal $journal): ?string`
  - `fullDescription(Journal $journal): ?string`
- `ModelType implements TypeInterface` — used for `type=model`. Public extras:
  - constants `SCHEMA_MODEL`, `SCHEMA_CONFIG`, `SCHEMA_MULTIPLE_ENCODED` (used in entity `schema` config).
  - `getData(\Illuminate\Database\Eloquent\Model $model, string $event): ?array` — builds the diff payload (`old/new/schema_*/attribute_names_*`). Throws `LogicException` if the model is not configured or lacks `EloquentValidation\ModelTrait`. Returns `null` when an `update` produced no changes.
- `IntegrationType implements TypeInterface` — used for `type=integration`. Renders `eloquent_journal::handler.integration`.
- `MetricType implements TypeInterface` — used for `type=metric`. Renders `eloquent_journal::handler.metric` and runs all string keys/values through `trans()`.

To add a new type, implement `TypeInterface` and add `'<type>' => ['bind' => YourHandler::class, 'title' => '...']` to `config('eloquent_journal.type')`.

### Controllers

- `AnourValar\EloquentJournal\Http\Controllers\Api\JournalController` — JSON listing endpoint. Wire with `Route::any('/', 'index')` under your auth/permission middleware.
- `AnourValar\EloquentJournal\Http\Controllers\Web\JournalController` — Blade UI. Renders the published views under `resources/views/vendor/eloquent_journal`.

## Usage examples

### Installation

```bash
composer require anourvalar/eloquent-journal
php artisan vendor:publish \
    --provider="AnourValar\\EloquentJournal\\Providers\\AnourValarEloquentJournalServiceProvider"
php artisan migrate
```

Schedule pruning in `routes/console.php` (Laravel 11+) or the console kernel:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune --path=app')
    ->dailyAt('00:00')
    ->runInBackground()
    ->onOneServer();
```

### Auto-logging Eloquent changes

```php
// config/eloquent_journal.php
use AnourValar\EloquentJournal\Handlers\ModelType;

return [
    'model' => App\Journal::class,

    'entity' => [
        App\Payment::class => [
            'title' => 'eloquent_journal::journal.entity.payment',
            'schema' => [
                'data.user_ids' => ['type' => ModelType::SCHEMA_MODEL, 'model' => App\User::class, 'display' => 'name'],
                'amount'        => ['type' => ModelType::SCHEMA_MULTIPLE_ENCODED],
            ],
            'attribute_names'   => [],
            'observe'           => true,   // auto-attach the Observer
            'exclude_attributes' => [],
        ],
    ],
    // ...
];
```

Register the morph alias somewhere bootstrappable (e.g. `AppServiceProvider::boot`):

```php
use Illuminate\Database\Eloquent\Relations\Relation;

Relation::enforceMorphMap([
    'payment' => \App\Payment::class,
]);
```

### Manual capture

```php
use AnourValar\EloquentJournal\Service;

$service = app(Service::class);

// Model change (typically called by the bundled Observer, but can be invoked manually)
$service->captureModel('update', $payment, dataExtra: ['source' => 'cli']);

// Integration call (supports anourvalar/http-client Response objects)
$service->captureIntegration(
    event: 'mts_sms_send',
    entity: $user,
    data: $httpResponse,           // \AnourValar\HttpClient\Response | array | null
    success: $httpResponse->successful(),
    tags: ['sms', 'mts'],
);

// Metric for an unauthenticated visitor
$service
    ->user(null)
    ->ipAddress($request->ip())
    ->captureMetric('user_token_obtain', $user);
```

### Reacting to writes

```php
use AnourValar\EloquentJournal\Events\JournalCreated;
use Illuminate\Support\Facades\Event;

Event::listen(function (JournalCreated $event) {
    logger()->info('journal #' . $event->journal->id);
});
```

### Routing the bundled controllers

```php
use AnourValar\EloquentJournal\Http\Controllers\Api\JournalController as ApiJournal;
use AnourValar\EloquentJournal\Http\Controllers\Web\JournalController as WebJournal;

Route::prefix('/journal')
    ->controller(ApiJournal::class)
    ->group(function () {
        Route::any('/', 'index')
            ->can('admin.administration')
            ->middleware('auth:sanctum', 'throttle:lax');
    });

Route::prefix('/journal')
    ->name('journal.')
    ->controller(WebJournal::class)
    ->group(function () {
        Route::any('/', 'index')->can('admin.administration')->name('index');
    });
```

## Conventions / gotchas

- **PHP 8.4 + Laravel 10/11/12/13** are required (`composer.json`). Hard dependencies include `anourvalar/eloquent-request`, `anourvalar/eloquent-validation`, `anourvalar/laravel-atom`, and `anourvalar/laravel-form`.
- **Morph map is mandatory.** `Service::getEntity()` throws `RuntimeException('MorphMap must be configured.')` if the model has no morph alias.
- **Models must use `AnourValar\EloquentValidation\ModelTrait`.** `ModelType::getData()` throws `LogicException('The model [...] is not supported.')` otherwise; it also requires the model to appear in `config('eloquent_journal.entity')` with a `schema` key.
- **Event vocabulary is enforced.** `captureModel` only accepts `create|update|delete|restore`; `captureIntegration` and `captureMetric` reject those four. All event keys must also exist in `config('eloquent_journal.event')`.
- **`captureModel` may return `null`** when an `update` produced no diff after `cleanData()`. Always allow `?Journal`.
- **Hidden / encrypted attributes are SHA-256 hashed** before being stored (`hash('sha256', ...) . ' [HASH]'`) — they are intentionally never written in clear text.
- **`updated_at` and `$computed` columns are always stripped** from diffs, alongside whatever you list under `entity.<class>.exclude_attributes`.
- **Publish & extend the model.** The published stub at `app/Journal.php` extends `AnourValar\EloquentJournal\Journal`; `config('eloquent_journal.model')` must point at it. Customise table/connection there.
- **ACL.** `scopeAcl()` restricts non-`admin.administration` users to their own rows and only events flagged `is_public => true`.
- **Pruning.** `Journal::prunable()` removes rows older than 12 months; override in your subclass if you need a different retention window. Run via `model:prune --path=app`.
- **`IntegrationException` is reportable.** Throwing it triggers `report()`, which lazily logs the failed integration via `Service::captureIntegration(..., success: false)` — do not also call `captureIntegration` manually for the same failure.
- **Localisation namespace is `eloquent_journal::`** (published to `lang/vendor/eloquent_journal`); views live under `eloquent_journal::handler.*` and `eloquent_journal::web.*`.
