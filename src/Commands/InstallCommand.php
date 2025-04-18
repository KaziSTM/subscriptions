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

        $this->publishMigrationsWithTimestamps(
            $this->packagePath('database/migrations'),
            database_path('migrations'),
            [
                'create_plans_table',
                'create_plan_limitations_table',
                'create_plan_features_table',
                'create_plan_subscriptions_table',
                'create_plan_subscription_usage_table',
            ]
        );
    }

    protected function publishModels(): void
    {
        $this->publishFiles(
            $this->packagePath('src/Models'),
            app_path('Models'),
            [
                'Plan',
                'Feature',
                'Limitation',
                'Subscription',
                'Usage',
            ]
        );
    }

    protected function publishFiles(string $from, string $to, array $files): void
    {
        $filesystem = app(Filesystem::class);

        foreach ($files as $file) {
            $source = "{$from}/{$file}.php";
            $target = "{$to}/{$file}.php";

            if (!file_exists($target)) {
                $filesystem->ensureDirectoryExists($to);
                $filesystem->copy($source, $target);
                $this->info("  - Published: {$file}.php");
            } else {
                $this->warn("  - Skipped: {$file}.php already exists.");
            }
        }
    }

    protected function publishMigrationsWithTimestamps(string $from, string $to, array $files): void
    {
        $filesystem = app(Filesystem::class);

        foreach ($files as $index => $file) {
            $timestamp = now()->addSeconds($index)->format('Y_m_d_His');
            $source = "{$from}/{$file}.php";
            $target = "{$to}/{$timestamp}_{$file}.php";

            if (!file_exists($target)) {
                $filesystem->ensureDirectoryExists($to);
                $filesystem->copy($source, $target);
                $this->info("  - Published: {$timestamp}_{$file}.php");
            } else {
                $this->warn("  - Skipped: {$file}.php already exists.");
            }
        }
    }

    protected function packagePath(string $path = ''): string
    {
        return dirname(__DIR__, 2) . ($path ? "/{$path}" : '');
    }
}
