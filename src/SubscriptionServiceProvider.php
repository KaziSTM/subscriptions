<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions;

use Illuminate\Routing\Router;
use KaziSTM\Subscriptions\Commands\InstallCommand;
use KaziSTM\Subscriptions\Http\Middleware\CheckPlanFeatures;
use KaziSTM\Subscriptions\Http\Middleware\CheckSubscription;
use Spatie\LaravelPackageTools\Commands\InstallCommand as SpatieInstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SubscriptionServiceProvider extends PackageServiceProvider
{
    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('subscription.active', CheckSubscription::class);
        $router->aliasMiddleware('subscription.feature', CheckPlanFeatures::class);
    }

    public function configurePackage(Package $package): void
    {
        $package->name('subscriptions')
            ->hasConfigFile('subscriptions')
            ->hasMigrations($this->getMigrations())
            ->hasInstallCommand(function (SpatieInstallCommand $command): void {
                $command->publishConfigFile()->publishMigrations();
            });
    }

    public function packageBooted(): void
    {
        parent::packageBooted();
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishConfig();
            $this->publishMigrations();
        }
    }

    private function getMigrations(): array
    {
        return [
            'create_plans_table',
            'create_limitations_table',
            'create_plan_features_table',
            'create_plan_subscriptions_table',
            'create_plan_subscription_usage_table',
        ];
    }

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/subscriptions.php' => config_path('subscriptions.php'),
        ], 'subscriptions-config');
    }

    private function publishMigrations(): void
    {
        $this->publishes([
            __DIR__ . '/../database/migrations/create_plans_table.php.stub' => database_path('migrations/create_plans_table.php'),
            __DIR__ . '/../database/migrations/create_limitations_table.php.stub' => database_path('migrations/create_limitations_table.php'),
            __DIR__ . '/../database/migrations/create_plan_features_table.php.stub' => database_path('migrations/create_plan_features_table.php'),
            __DIR__ . '/../database/migrations/create_plan_subscriptions_table.php.stub' => database_path('migrations/create_plan_subscriptions_table.php'),
            __DIR__ . '/../database/migrations/create_plan_subscription_usage_table.php.stub' => database_path('migrations/create_plan_subscription_usage_table.php'),
        ], 'subscriptions-migrations');
    }
}
