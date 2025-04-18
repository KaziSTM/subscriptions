<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions;

use KaziSTM\Subscriptions\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Commands\InstallCommand as SpatieInstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SubscriptionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('subscriptions')
            ->hasConfigFile('subscriptions')
            ->hasMigrations([
                'create_plans_table',
                'create_limitations_table',
                'create_plan_features_table',
                'create_plan_subscriptions_table',
                'create_plan_subscription_usage_table',
            ])
            ->hasInstallCommand(function (SpatieInstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations();
            });
    }

    public function packageBooted(): void
    {
        // Register the custom install command
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            // Explicitly define publish tags for migrations and config
            $this->publishes([
                __DIR__ . '/../config/subscriptions.php' => config_path('subscriptions.php'),
            ], 'subscriptions-config');

            $this->publishes([
                __DIR__ . '/../database/migrations/create_plans_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_plans_table.php'),
                __DIR__ . '/../database/migrations/create_limitations_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() + 1) . '_create_limitations_table.php'),
                __DIR__ . '/../database/migrations/create_plan_features_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() + 2) . '_create_plan_features_table.php'),
                __DIR__ . '/../database/migrations/create_plan_subscriptions_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() + 3) . '_create_plan_subscriptions_table.php'),
                __DIR__ . '/../database/migrations/create_plan_subscription_usage_table.php.stub' => database_path('migrations/' . date('Y_m_d_His', time() + 4) . '_create_plan_subscription_usage_table.php'),
            ], 'subscriptions-migrations');
        }
    }
}
