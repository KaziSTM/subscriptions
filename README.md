# KaziSTM Subscriptions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kazi-stm/subscriptions.svg?style=flat-square)](https://packagist.org/packages/kazistm/subscriptions)
[![Total Downloads](https://img.shields.io/packagist/dt/kazi-stm/subscriptions.svg?style=flat-square)](https://packagist.org/packages/kazistm/subscriptions)
[![License](https://img.shields.io/packagist/l/kazi-stm/subscriptions?style=flat-square)](https://github.com/KaziSTM/subscriptions/blob/main/LICENSE.md)
**A flexible and extendable subscription and plan management system for Laravel applications, particularly suited for SaaS products.** This package provides tools to manage recurring plans, features with usage limits, and subscription lifecycles.

This package is based on, and aims to enhance, the foundation laid by `laravelcm/laravel-subscriptions`.

## Table of Contents

* [Key Features & Enhancements](#key-features--enhancements)
* [Requirements](#requirements)
* [Installation](#installation)
* [Configuration](#configuration)
* [Database Migrations](#database-migrations)
* [Core Concepts & Models](#core-concepts--models)
    * [1. Subscribable Model](#1-subscribable-model)
    * [2. Plan (KaziSTM\Subscriptions\Models\Plan)](#2-plan-kazistmsubscriptionsmodelsplan)
    * [3. Limitation (KaziSTM\Subscriptions\Models\Limitation)](#3-limitation-kazistmsubscriptionsmodelslimitation)
    * [4. Feature (KaziSTM\Subscriptions\Models\Feature)](#4-feature-kazistmsubscriptionsmodelsfeature)
    * [5. Subscription (KaziSTM\Subscriptions\Models\Subscription)](#5-subscription-kazistmsubscriptionsmodelssubscription)
    * [6. Subscription Usage (KaziSTM\Subscriptions\Models\SubscriptionUsage)](#6-subscription-usage-kazistmsubscriptionsmodelssubscriptionusage)
    * [Note on Slugs vs Names/Titles](#note-on-slugs-vs-namestitles)
* [Usage](#usage)
    * [Preparation](#preparation)
    * [Managing Subscriptions](#managing-subscriptions)
    * [Managing Feature Usage](#managing-feature-usage)
* [Middleware](#middleware)
    * [Registration (Laravel 11+)](#registration-laravel-11)
    * [Registration (Pre-Laravel 11)](#registration-pre-laravel-11)
    * [Usage in Routes](#usage-in-routes)
    * [Subscribable Entity Detection](#subscribable-entity-detection)
* [Extending Models](#extending-models)
* [Support & Issues](#support--issues)
* [License](#license)
* [Credits](#credits)

## Key Features & Enhancements

* Manages Plans, Subscriptions, Features, and Usage tracking.
* **Introduces a Limitation model:** Logically groups related features (e.g., 'Build Minutes', 'Seats', 'Storage Size'), providing translatable titles and descriptions. Features now primarily hold the *value* or quota, inheriting context from the Limitation.
* Supports trial periods, grace periods, plan changes, cancellations, and renewals.
* Features can have usage recorded against them, with optional periodic resetting (e.g., monthly email quotas).
* Includes an `artisan subscriptions:install` command for easy setup (config, migrations, optional model stubs).
* Includes Middleware for checking active subscriptions (`subscription.active`) and plan features (`subscription.feature`).
* Provides helper relationships (`limitations()`) and scopes (`scopeWhereHasLimitationSlug()`) on the Plan model.
* Uses Spatie packages for core functionalities like slugs (`spatie/laravel-sluggable`), translatable fields (`spatie/laravel-translatable`), and sortable models (`spatie/eloquent-sortable`).
* Built with `spatie/laravel-package-tools` for standard package structure.

## Requirements

* PHP: >= 8.2
* Laravel: >= 10.x

## Installation

1.  Install the package via Composer:
    ```bash
    composer require kazistm/subscriptions
    ```

2.  Run the installation command:
    ```bash
    php artisan subscriptions:install
    ```

    This interactive command will:
    * Ask if you want to publish the **configuration file** (`config/subscriptions.php`). Defaults to yes.
    * Publish the necessary **database migrations** to your `database/migrations` folder (it checks for existing migrations with the same base name to avoid duplication).
    * Ask if you want to publish basic **model stubs** (`Plan.php`, `Subscription.php`, etc.) to your `app/Models` directory. This is optional and only needed if you plan to directly extend or override the package's models. Defaults to no.
    * Automatically run `php artisan migrate` to create the required database tables.

## Configuration

The main configuration file is located at `config/subscriptions.php` after publishing.

* **Publishing (if skipped during install):**
    ```bash
    php artisan vendor:publish --tag=subscriptions-config
    ```

* **Options:**
    * `tables`: Customize the names of the database tables used by the package. Defaults are usually fine.
    * `models`: Define which Eloquent models the package should use. This allows you to override the default models with your own extended versions (see Extending Models section).

## Database Migrations

The `subscriptions:install` command handles publishing and running migrations. The following tables are created (using default names):

* `plans`: Stores subscription plans details.
* `limitations`: Defines types of features/limits (e.g., 'users', 'projects').
* `features`: Links Plans to Limitations and sets the value/quota.
* `subscriptions`: Links subscribable models (e.g., Users) to Plans and tracks lifecycle.
* `subscription_usage`: Tracks consumption of resettable features.

## Core Concepts & Models

### 1. Subscribable Model

Your model that will have subscriptions (typically `App\Models\User`, but can be any model like `App\Models\Company`) needs to use the `KaziSTM\Subscriptions\Traits\HasPlanSubscriptions` trait.

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use KaziSTM\Subscriptions\Traits\HasPlanSubscriptions; // Import the trait

class User extends Authenticatable // or your base model (e.g., Model)
{
    use HasPlanSubscriptions; // Use the trait

    // ... rest of your model
}
```
### 2. Plan (KaziSTM\Subscriptions\Models\Plan)

Defines a subscription plan your users can subscribe to.

| Attribute        | Type      | Description                                      |
|------------------|-----------|--------------------------------------------------|
| name             | json      | Translatable display name                        |
| slug             | string    | Unique identifier                                |
| description      | json      | Translatable description                         |
| is_active        | boolean   | If plan is usable                                |
| price            | decimal   | Cost per interval                                |
| signup_fee       | decimal   | One-time fee                                     |
| currency         | string    | e.g., "USD"                                      |
| trial_period     | int       | Duration of trial                                |
| trial_interval   | string    | Unit: day, month, year (from Interval Enum)      |
| invoice_period   | int       | Duration of billing cycle                        |
| invoice_interval | string    | Unit: day, month, year (from Interval Enum)      |
| grace_period     | int       | Duration of grace period                         |
| grace_interval   | string    | Unit: day, month, year (from Interval Enum)      |
| sort_order       | int       | Ordering                                         |
| deleted_at       | timestamp | Soft deletes                                     |


## Relationships

- `features()`
- `subscriptions()`
- `limitations()` (HasManyThrough Features)

## Scopes

- `scopeWhereHasLimitationSlug(Builder $query, string $limitationSlug)`

## Methods

- `hasLimitation(string $limitationSlug)`
- `isFree()`
- `hasTrial()`
- `hasGrace()`
- `getFeatureBySlug()`
- `activate()`
- `deactivate()`)

> **Note:** The `Interval` Enum (`KaziSTM\Subscriptions\Enums\Interval`) typically uses values like `Interval::DAY->value` (`'day'`), `Interval::MONTH->value` (`'month'`), `Interval::YEAR->value` (`'year'`) when interacting with these fields programmatically, although the database stores the string values.


## 3. Limitation (`KaziSTM\Subscriptions\Models\Limitation`)

Defines a category or type of feature/limit (the "what").

### Attributes

| Attribute    | Type      | Description                                 |
|--------------|-----------|---------------------------------------------|
| title        | json      | Translatable display title (e.g., "User Seats") |
| slug         | string    | Unique identifier (e.g., "users", "projects") |
| description  | json      | Translatable description                    |
| type         | string    | Custom type field (optional)               |
| sort_order   | int       | Ordering                                   |
| deleted_at   | timestamp | Soft deletes                               |

### Relationships

- `features()`

## 4. Feature (`KaziSTM\Subscriptions\Models\Feature`)

Links a Plan to a Limitation, defining the specific value/quota (the "how much").

### Attributes

| Attribute           | Type        | Description                                                                 |
|---------------------|-------------|-----------------------------------------------------------------------------|
| plan_id             | foreignId   | Belongs to Plan                                                             |
| limitation_id       | foreignId   | Belongs to Limitation                                                       |
| value               | string      | Limit/value (e.g., "10", "500", "true"). Use strings for consistency.       |
| resettable_period   | int         | How often usage resets (e.g., 1 for 1 month)                                |
| resettable_interval | string      | Unit for reset (day, month, year). Use 0/null if usage never resets.       |
| sort_order          | int         | Ordering                                                                    |
| deleted_at          | timestamp   | Soft deletes                                                                |

### Relationships

- `plan()`
- `limitation()`
- `usages()`

### Methods

- `getResetDate()`

---

## 5. Subscription (`KaziSTM\Subscriptions\Models\Subscription`)

Links a subscribable model to a Plan and tracks its lifecycle.

### Attributes

| Attribute        | Type        | Description                                                            |
|------------------|-------------|------------------------------------------------------------------------|
| subscriber_id    | int         | ID of the subscribing model                                           |
| subscriber_type  | string      | Class name of the subscribing model                                   |
| plan_id          | foreignId   | The Plan being subscribed to                                          |
| name             | json        | Translatable identifier (e.g., "main", "default")                     |
| slug             | string      | Unique slug for this subscription instance                            |
| trial_ends_at    | datetime    | When the trial period ends                                            |
| starts_at        | datetime    | When the current billing period started                               |
| ends_at          | datetime    | When the current billing period ends                                  |
| cancels_at       | datetime    | If scheduled, when cancellation takes effect                          |
| canceled_at      | datetime    | When the cancellation was requested/marked                            |
| deleted_at       | timestamp   | Soft deletes                                                           |

### Relationships

- `subscriber()` *(MorphTo)*
- `plan()`
- `usage()`

### Methods

- `active()`
- `inactive()`
- `onTrial()`
- `canceled()`
- `ended()`
- `cancel()`
- `changePlan()`
- `renew()`
- `recordFeatureUsage()`
- `reduceFeatureUsage()`
- `canUseFeature()`
- `getFeatureUsage()`
- `getFeatureRemainings()`
- `getFeatureValue()`

### Scopes

- `ofSubscriber()`
- `findEndingTrial()`
- `findEndedTrial()`
- `findEndingPeriod()`
- `findEndedPeriod()`
- `findActive()`

### 6. Subscription Usage (`KaziSTM\Subscriptions\Models\SubscriptionUsage`)

Tracks consumption of specific, resettable features.

#### Attributes

| Attribute        | Type       | Description                                                  |
|------------------|------------|--------------------------------------------------------------|
| `subscription_id`| `foreignId`| The subscription instance                                   |
| `feature_id`     | `foreignId`| The specific feature being tracked                          |
| `used`           | `int`      | How much has been used in the current period                |
| `valid_until`    | `datetime` | When the current usage period ends and resets               |
| `deleted_at`     | `timestamp`| Soft deletes                                                 |

#### Relationships

- `subscription()`
- `feature()`

#### Scopes

- `scopeByFeatureSlug()`

#### Methods

- `expired()`

### Note on Slugs vs Names/Titles

Throughout the package, `slug` attributes (on `Plan`, `Limitation`, and `Subscription`) serve as unique, immutable identifiers intended for **programmatic use**. These are what you'll use in your code when:

- Fetching specific records
- Checking features
- Applying middleware

For example:
```php
$subscription->planSubscription('main');
$subscription->canUseFeature('api-calls');
```

## Usage

### Preparation

#### 1. Add Trait

Ensure your subscribable model(s) (e.g., `App\Models\User`, `App\Models\Company`) use the `KaziSTM\Subscriptions\Traits\HasPlanSubscriptions` trait.

```php
use KaziSTM\Subscriptions\Traits\HasPlanSubscriptions;

class User extends Authenticatable
{
    use HasPlanSubscriptions;
}
```

#### 2. Create Plans, Limitations & Features
Define plans, limitations, and features in your database, typically using seeders.

Example Seeder Logic:

```php
use KaziSTM\Subscriptions\Models\Plan;
use KaziSTM\Subscriptions\Models\Limitation;

// Create Limitations (the "what")
$usersLimit = Limitation::firstOrCreate(['slug' => 'users'], ['title' => ['en' => 'User Seats']]);
$projectsLimit = Limitation::firstOrCreate(['slug' => 'projects'], ['title' => ['en' => 'Projects']]);
$apiLimit = Limitation::firstOrCreate(['slug' => 'api-calls'], ['title' => ['en' => 'API Calls per Month']]);
$supportLimit = Limitation::firstOrCreate(['slug' => 'priority-support'], ['title' => ['en' => 'Priority Support']]);

// Create Basic Plan
$basicPlan = Plan::firstOrCreate(['slug' => 'basic'], [
'name' => ['en' => 'Basic Plan'],
'price' => 9.99,
'invoice_interval' => 'month',
'invoice_period' => 1,
'currency' => 'USD'
]);

// Assign Feature values (the "how much")
$basicPlan->features()->updateOrCreate(['limitation_id' => $usersLimit->id], ['value' => '5']);
$basicPlan->features()->updateOrCreate(['limitation_id' => $projectsLimit->id], ['value' => '10']);
$basicPlan->features()->updateOrCreate(['limitation_id' => $apiLimit->id], ['value' => '1000', 'resettable_interval' => 'month', 'resettable_period' => 1]);
$basicPlan->features()->updateOrCreate(['limitation_id' => $supportLimit->id], ['value' => 'false']); // No priority support

// Create Pro Plan
$proPlan = Plan::firstOrCreate(['slug' => 'pro'], [
'name' => ['en' => 'Pro Plan'],
'price' => 29.99,
'invoice_interval' => 'month',
'invoice_period' => 1,
'currency' => 'USD',
'trial_period' => 14,
'trial_interval' => 'day'
]);

// Assign Feature values
$proPlan->features()->updateOrCreate(['limitation_id' => $usersLimit->id], ['value' => '25']);
$proPlan->features()->updateOrCreate(['limitation_id' => $projectsLimit->id], ['value' => '50']);
$proPlan->features()->updateOrCreate(['limitation_id' => $apiLimit->id], ['value' => '10000', 'resettable_interval' => 'month', 'resettable_period' => 1]);
$proPlan->features()->updateOrCreate(['limitation_id' => $supportLimit->id], ['value' => 'true']); // Has priority support
```

# Managing Subscriptions

```php
use KaziSTM\Subscriptions\Models\Plan;
use App\Models\User; // Your subscribable model

$user = User::find(1);
$proPlan = Plan::where('slug', 'pro')->firstOrFail();
$premiumPlan = Plan::where('slug', 'premium')->first(); // Assuming a premium plan exists

// --- Create / Retrieve ---
// Create a new subscription named 'main'. The name helps if a model can have multiple subscriptions.
$subscription = $user->newPlanSubscription('main', $proPlan);

// Retrieve the 'main' subscription later
$subscription = $user->planSubscription('main');

// --- Check Status ---
if ($subscription?->active()) { echo "Active!"; } // Not ended, could be on trial or paid period
if ($subscription?->inactive()) { echo "Inactive!"; } // Ended or canceled
if ($subscription?->onTrial()) { echo "On Trial until " . $subscription->trial_ends_at->toDateString(); }
if ($subscription?->canceled()) { echo "Canceled."; } // Cancellation requested/processed
if ($subscription?->ended()) { echo "Current period ended."; } // ends_at is in the past

// --- Modify ---
if ($subscription && $premiumPlan) {
    $subscription->changePlan($premiumPlan); // Change plan (handles proration based on config/logic)
    echo "Plan changed!";
}

// Cancel subscription at the end of the current billing period
$subscription?->cancel();
// Cancel immediately (ends the current period now)
// $subscription?->cancel(true);

// Renew subscription (e.g., after manual payment or admin action)
// Resets usage for resettable features and sets a new billing cycle (starts_at, ends_at)
// Throws LogicException if subscription was canceled AND has already ended
try {
    $subscription?->renew();
    echo "Renewed!";
} catch (\LogicException $e) { /* Handle error */ }

// --- Querying ---
$isSubscribedToPro = $user->subscribedTo($proPlan->id); // Check active subscription to specific plan ID
$activePlans = $user->subscribedPlans(); // Get collection of Plan models user is actively subscribed to
$allUserSubscriptions = $user->planSubscriptions; // Get collection of Subscription models for user
$activeUserSubscriptions = $user->activePlanSubscriptions(); // Get collection of active Subscription models for user

// --- Plan Queries ---
// Find plans that offer a certain feature (by Limitation slug)
$plansWithApi = Plan::whereHasLimitationSlug('api-calls')->get();

// Check if a specific plan instance offers a feature
$proPlanHasSupport = $proPlan->hasLimitation('priority-support'); // true
```

# Managing Feature Usage

Interact with features using the slug of the Limitation model.

```php
// Assuming $user and $subscription are available
$subscription = $user->planSubscription('main');

$usersSlug = 'users';
$apiCallsSlug = 'api-calls';
$supportSlug = 'priority-support';

// --- Checking Ability ---
// canUseFeature checks boolean features AND depleting quotas
if ($subscription?->canUseFeature($usersSlug)) {
    echo "User limit not reached.";
    // ... perform action like creating a user ...
    // Then record usage:
    $subscription->recordFeatureUsage($usersSlug); // Increment by 1
} else {
    echo "User limit reached!";
}

if ($subscription?->canUseFeature($supportSlug)) {
    echo "Priority support is enabled for this plan.";
}

// --- Recording Usage ---
// Increment usage (default)
$usage = $subscription?->recordFeatureUsage($apiCallsSlug, 15); // Record 15 calls
if ($usage) echo "Recorded usage for {$apiCallsSlug}. New usage: {$usage->used}";

// Set usage to a specific value (non-incremental)
$usage = $subscription?->recordFeatureUsage('storage-gb', 15.5, false);

// --- Reducing Usage ---
// Useful when a resource tied to a quota is removed
$usage = $subscription?->reduceFeatureUsage($usersSlug, 1); // Decrement by 1
if ($usage) echo "Reduced usage for {$usersSlug}. New usage: {$usage->used}";

// --- Retrieving Usage Data ---
$currentApiUsage = $subscription?->getFeatureUsage($apiCallsSlug);
$remainingApiCalls = $subscription?->getFeatureRemainings($apiCallsSlug);
$apiLimitOnPlan = $subscription?->getFeatureValue($apiCallsSlug); // Gets the value from the features table

echo "API Calls: Used={$currentApiUsage}, Limit={$apiLimitOnPlan}, Remaining={$remainingApiCalls}";

// --- Resets ---
// If 'api-calls' feature has resettable_interval='month' and resettable_period=1,
// when recordFeatureUsage() is called after the 'valid_until' date on the usage record,
// the 'used' count automatically resets to 0, and 'valid_until' is updated for the next period.
```
# Middleware

Protect your application routes based on subscription status or included features. The middleware automatically attempts to detect the subscribable entity from Route Model Binding, Filament Tenancy, or the Authenticated User.

## Registration (Laravel 11+)

Register the middleware aliases in your `bootstrap/app.php` file:

```php
// bootstrap/app.php
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(...)
    ->withRouting(...)
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'subscription.active' => \KaziSTM\Subscriptions\Http\Middleware\CheckSubscription::class,
            'subscription.feature' => \KaziSTM\Subscriptions\Http\Middleware\CheckPlanFeatures::class,
            // Add other aliases here
        ]);

        // Optionally add globally to groups if needed
        // $middleware->web(append: [ ... ]);
    })
    ->withExceptions(...)
    ->create();
```

## Registration (Pre-Laravel 11)
Register the middleware aliases in your app/Http/Kernel.php:

```php
// app/Http/Kernel.php
protected $middlewareAliases = [ // Or $routeMiddleware in older versions
// ... other aliases
'subscription.active' => \KaziSTM\Subscriptions\Http\Middleware\CheckSubscription::class,
'subscription.feature' => \KaziSTM\Subscriptions\Http\Middleware\CheckPlanFeatures::class,
];
```

## Usage in Routes

### Check Active Subscription (`subscription.active`)

Verifies the relevant subscribable entity has an active subscription (not ended or canceled). Aborts with 403 (Forbidden) otherwise.

```php
// routes/web.php or routes/api.php

// Checks Auth::user()'s 'main' subscription
Route::get('/dashboard', /* ... */)->middleware(['auth', 'subscription.active']);

// Checks 'main' subscription for the {company} route model (if Company uses the trait)
Route::get('/company/{company}/settings', /* ... */)->middleware(['auth', 'subscription.active']);

// Checks a specific subscription named 'pro_tools' (applies to the found entity)
Route::get('/pro-feature', /* ... */)->middleware(['auth', 'subscription.active:pro_tools']);
```

### Check Plan Features (`subscription.feature`)

Verifies the relevant subscribable entity's active subscription plan includes all specified features (by Limitation slug). Aborts with 403 (Forbidden) otherwise. Implicitly checks for an active subscription first.

```php
// routes/web.php or routes/api.php

// Requires the plan to have the 'users' limitation defined
Route::get('/users/invite', /* ... */)->middleware(['auth', 'subscription.feature:users']);

// Requires both 'reports' and 'exports' limitations
Route::get('/reports/export', /* ... */)->middleware(['auth', 'subscription.feature:reports,exports']);

// Check a specific subscription ('pro_tools') for a specific feature ('advanced-analytics')
Route::get('/analytics/deep-dive', /* ... */)
    ->middleware(['auth', 'subscription.feature:advanced-analytics,pro_tools']); // Feature slug first, optional subscription slug second

```

### Subscribable Entity Detection

The middleware automatically attempts to determine the subscribable entity to check in the following order of precedence:

1. **Route Model Binding**  
   Looks for a route parameter that resolves to a model using the `HasPlanSubscriptions` trait.

2. **Filament Tenancy**  
   If [`filament/filament`](https://filamentphp.com) is installed, checks the current tenant model.

3. **Authenticated User**  
   Falls back to `Auth::user()` as a default.

## Extending Models

If you need custom logic or relationships on the package's models:

1. Run `php artisan subscriptions:install`.
2. Answer "yes" when asked to Publish model stubs...?
3. Edit the generated files in `app/Models/` (e.g., `app/Models/Plan.php`). They extend the base package models using aliases.

```php
<?php
namespace App\Models;

use KaziSTM\Subscriptions\Models\Plan as BasePlan; // Alias is used

class Plan extends BasePlan
{
    // Your custom logic here...
}
```
4. Update `config/subscriptions.php` to use your models:

```php
// config/subscriptions.php
'models' => [
    'plan' => \App\Models\Plan::class,
    'limitation' => \App\Models\Limitation::class,
    'feature' => \App\Models\Feature::class,
    'subscription' => \App\Models\Subscription::class,
    'subscription_usage' => \App\Models\SubscriptionUsage::class,
],
```


## Support & Issues

If you discover any **security-related issues**, please email **[email address removed]** instead of using the issue tracker.

All other issues, feature requests, or questions should be submitted via the [GitHub Issues](https://github.com/KaziSTM/subscriptions/issues) page.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- Forked from and inspired by LaravelCM Subscriptions
- Inspired by patterns in Spatie open-source packages.
- Author: [Nezrek Youcef](ynezrek@netgrid.dev)

