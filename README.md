# KaziSTM Subscriptions

**A flexible and extendable subscription system for Laravel, built upon [laravelcm/laravel-subscriptions](https://github.com/laravelcm/laravel-subscriptions) — with enhancements.**

This package provides all the tools needed to manage recurring plans, features, and usage limitations in your Laravel applications, particularly for SaaS products.

---

## ✨ What's Different from LaravelCM?

- ✅ Added a `Limitation` model to logically group similar features
- ✅ Removed `title`, `slug`, `description` from `Feature` model
- ✅ Features now only hold `value` — the definition is inherited from the associated `Limitation`
- ✅ Cleaner architecture and extended customization

---

## 📦 Installation

```bash
composer require kazistm/subscriptions
```

Then publish configuration, migrations, and stub models:

```bash
php artisan subscriptions:install
```

This will:

- Publish config: `config/subscriptions.php`
- Publish all necessary migrations
- Create extendable models under `App\Models` if they don't exist

---

## ⚙️ Configuration

The configuration file is located at `config/subscriptions.php`. You can override:

- Table names
- Model bindings
- Feature & limitation behaviors

---

## 🧩 Core Models

### 🧭 Plan

Defines a subscription plan.

| Field      | Type     | Description                |
|------------|----------|----------------------------|
| `name`     | `string` | Display name               |
| `slug`     | `string` | Unique identifier          |
| `price`    | `float`  | Cost of the plan           |
| `interval` | `enum`   | `day`, `month`, or `year`  |

---

### 🔒 Limitation

Represents a group/category of features. It has a translatable `name` and `description`, and can be reused across multiple plans.

| Field         | Type     | Description                    |
|---------------|----------|--------------------------------|
| `name`        | `string` | Internal identifier (slugged)  |
| `title`       | `array`  | Translatable display title     |
| `description` | `array`  | Translatable description       |

---

### 🧪 Feature

Represents a value for a `Limitation` on a plan. A `Feature` now only holds a numeric or string `value`.

| Field           | Type   | Description                      |
|-----------------|--------|----------------------------------|
| `value`         | `mixed`| Value or quota for the feature   |
| `plan_id`       | `int`  | Related Plan                     |
| `limitation_id` | `int`  | Related Limitation               |

---

### 🔁 Subscription & Usage

- `Subscription` links a user or billable model to a plan.
- `Usage` tracks how much of a feature (limitation) has been consumed.

---

## 🛠 Usage Examples

### Assigning a Plan to a User

```php
$user->newSubscription('main', $plan);
```

### Checking Feature Usage

```php
$user->subscription('main')->canUse('emails');
$user->subscription('main')->recordUsage('emails');
```

> The `emails` key refers to the `slug` in the `limitations` table.

---

## 🧪 Testing

```bash
composer test
```

---

## 🧼 Linting & Static Analysis

```bash
composer lint     # Laravel Pint
composer types    # Larastan
```

---

## 📂 Folder Structure

```
src/
├── Models/
│   ├── Plan.php
│   ├── Feature.php
│   ├── Limitation.php ← NEW
│   └── ...
├── Traits/
├── Commands/
│   └── InstallCommand.php
└── ...
```

---

## ✅ Roadmap

- [x] Add `Limitation` support
- [x] Add `subscriptions:install` command
- [ ] Add support for tiered feature pricing
- [ ] Optional dashboard integration (Filament, Nova)

---

## 🪪 License

MIT © [Nezrek Youcef](mailto:ynezrek@netgrid.dev)

---

## 🙌 Credits

- Forked from [LaravelCM Subscriptions](https://github.com/laravelcm/laravel-subscriptions)
- Inspired by [Spatie](https://spatie.be/) open-source packages