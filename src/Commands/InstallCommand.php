<?php

namespace KaziSTM\Subscriptions\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    protected $signature = 'subscriptions:install';
    protected $description = 'Install the Subscriptions package (config, migrations, models)';

    public function handle(): void
    {
        $this->info('🔧 Installing Subscriptions Package...');

        $this->publishConfig();
        $this->publishMigrations();
        $this->publishModels();

        $this->info('✅ Subscriptions installed successfully!');
    }

    protected function publishConfig(): void
    {
        $this->callSilent('vendor:publish', [
            '--tag' => 'subscriptions-config',
            '--force' => true,
        ]);
        $this->info('📁 Config file published.');
    }

    protected function publishMigrations(): void
    {
        $this->info('📦 Publishing migrations...');

        $this->publishFiles(
            basePath: dirname(__DIR__, 2) . '/database/migrations',
            destination: database_path('migrations'),
            files: [
                'create_plans_table',
                'create_limitations_table',
                'create_plan_features_table',
                'create_plan_subscriptions_table',
                'create_plan_subscription_usage_table',
            ]
        );
    }

    protected function publishModels(): void
    {
        $this->publishFiles(
            basePath: dirname(__DIR__) . '/Models',
            destination: app_path('Models'),
            files: [
                'Plan',
                'Feature',
                'Limitation',
                'Subscription',
                'Usage',
            ]
        );
    }

    protected function publishFiles(string $basePath, string $destination, array $files): void
    {
        $filesystem = app(Filesystem::class);

        foreach ($files as $file) {
            $source = "{$basePath}/{$file}.php";
            $target = "{$destination}/{$file}.php";

            if (!file_exists($target)) {
                $filesystem->ensureDirectoryExists($destination);
                $filesystem->copy($source, $target);
                $this->info("  - Published: {$file}.php");
            } else {
                $this->warn("  - Skipped: {$file}.php already exists.");
            }
        }
    }
}
