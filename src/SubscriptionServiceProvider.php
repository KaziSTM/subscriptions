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
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
