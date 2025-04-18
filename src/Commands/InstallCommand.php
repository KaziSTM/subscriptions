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

        $this->publishResources();

        $this->info('✅ Subscriptions installed successfully!');
    }

    protected function publishResources(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->publishModels();
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

        $this->publishFilesFromStubs(
            __DIR__ . "/../../database/migrations",
            'migrations',
            [
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
        $this->publishFilesFromStubs(
            __DIR__ . "/../../stubs/Models",
            'Models',
            [
                'Plan',
                'Feature',
                'Limitation',
                'Subscription',
                'Usage',
            ]
        );
    }

    protected function publishFilesFromStubs(string $stubDir, string $type, array $files): void
    {
        $filesystem = app(Filesystem::class);

        foreach ($files as $file) {
            $source = "{$stubDir}/{$file}.php";
            $destination = app_path("{$type}/{$file}.php");

            if (!file_exists($destination)) {
                $filesystem->ensureDirectoryExists(app_path($type));
                $filesystem->copy($source, $destination);
                $this->info("  - Published: {$file}.php");
            } else {
                $this->warn("  - Skipped: {$file}.php already exists.");
            }
        }
    }
}
