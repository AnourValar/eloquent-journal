# Laravel Eloquent Journal

## Installation

**Step #1: Install the package**

```bash
composer require anourvalar/eloquent-journal
```

**Step #2: Publish the resources**

```bash
php artisan vendor:publish --provider=AnourValar\\EloquentJournal\\Providers\\AnourValarEloquentJournalServiceProvider
```

**Step #3: Schedule the prune command**

```bash
$schedule->command('model:prune --path=app')->dailyAt('00:00')->runInBackground()->onOneServer();
```


## Usage

**Step #1: Set up the eloquent_journal config**

**Step #2: Use the AnourValar\EloquentJournal\Service to capture events**

**Step #3: Api Controller**

```php
// Journal
Route::prefix('/journal')
    ->controller(AnourValar\EloquentJournal\Http\Controllers\Api\JournalController::class)
    ->group(function () {
        Route::any('/', 'index')
            ->can('admin.administration')
            ->middleware('auth:sanctum', 'throttle:lax');
    });
```

**Step #4: Web Controller**

```php
// admin.menu.journal / admin.journal.index / admin.administration / fa-history
// admin/journal
Route::prefix('/journal')
    ->name('journal.')
    ->controller(AnourValar\EloquentJournal\Http\Controllers\Web\JournalController::class)
    ->group(function () {
        Route::any('/', 'index')->can('admin.administration')->name('index');
    });
```
