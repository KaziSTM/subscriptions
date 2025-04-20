<?php

declare(strict_types=1);

namespace KaziSTM\Subscriptions\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem; // Import Filesystem facade/class

class InstallCommand extends Command
{
    protected $signature = 'subscriptions:install';

    protected $description = 'Install the Subscriptions package (config, migrations, models)';

    // Use Filesystem instance for operations
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    public function handle(): void
    {
        $this->info('🔧 Installing Subscriptions Package...');

        // 1. Publish Config (Optional)
        if ($this->confirm('Publish configuration file? (config/subscriptions.php)', true)) {
            $this->publishConfig();
        } else {
            $this->comment('Skipping config file publishing.');
        }

        // 2. Publish Migrations (Usually essential, but we check existence)
        $this->publishMigrations();

        // 3. Publish Model Stubs (Optional)
        if ($this->confirm('Publish model stubs to app/Models (for customization/extension)?', false)) {
            $this->publishModels();
        } else {
            $this->comment('Skipping model stub publishing.');
        }

        // 4. Suggest Running Migrations
        $this->info('Running database migrations...');
        $this->call('migrate'); // Run migrations automatically after potentially publishing new ones

        $this->info('✅ Subscriptions package installation tasks completed!');
        $this->comment('Please review the published configuration (if any) and ensure migrations ran successfully.');
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfig(): void
    {
        // Use vendor:publish without --force. It will prompt user if file exists.
        $this->call('vendor:publish', [
            '--tag' => 'subscriptions-config',
            // '--provider' => 'KaziSTM\\Subscriptions\\SubscriptionServiceProvider' // Optional: Be more specific
        ]);
    }

    /**
     * Publish migration files, adding timestamps if they don't already exist.
     */
    protected function publishMigrations(): void
    {
        $this->info('📦 Publishing migrations...');

        $published = $this->publishMigrationsWithTimestamps(
            $this->packagePath('database/migrations'), // Source directory in package
            database_path('migrations'), // Target directory in application
            [ // List of base migration file names (without timestamp or .php extension)
                'create_plans_table',
                'create_plan_limitations_table',
                'create_plan_features_table',
                'create_plan_subscriptions_table',
                'create_plan_subscription_usage_table',
            ]
        );

        if ($published) {
            $this->info('Database migrations published successfully.');
        } else {
            $this->info('No new migrations needed publishing.');
        }
    }

    /**
     * Publish empty model stubs that extend the package models.
     */
    protected function publishModels(): void
    {
        $this->info('📝 Publishing model stubs...');

        // Define package models and their base namespace
        $models = [
            'Plan',
            'Feature',
            'Limitation',
            'Subscription',
            'SubscriptionUsage',
        ];
        $baseModelNamespace = 'KaziSTM\\Subscriptions\\Models';
        $appModelsPath = app_path('Models');

        // Ensure the app/Models directory exists
        $this->filesystem->ensureDirectoryExists($appModelsPath);

        $publishedSomething = false;
        foreach ($models as $modelName) {
            $targetPath = "{$appModelsPath}/{$modelName}.php";

            // Check if the stub file already exists in app/Models
            if (! $this->filesystem->exists($targetPath)) {
                $this->createEmptyModelStub($targetPath, $modelName, $baseModelNamespace);
                $this->line("  <info>Created Stub:</info> {$modelName}.php");
                $publishedSomething = true;
            } else {
                $this->line("  <fg=yellow>Skipped:</> {$modelName}.php already exists.");
            }
        }

        if ($publishedSomething) {
            $this->info('Model stubs published successfully to app/Models/.');
        } else {
            $this->info('No new model stubs needed publishing.');
        }
    }

    /**
     * Creates an empty model stub file extending the base package model.
     *
     * @param  string  $targetPath  The full path where the stub file should be created.
     * @param  string  $modelName  The short name of the model (e.g., "Plan").
     * @param  string  $baseModelNamespace  The base namespace of the package models.
     */
    protected function createEmptyModelStub(string $targetPath, string $modelName, string $baseModelNamespace): void
    {
        // Determine the FQCN of the base model in the package
        $baseModelFqcn = "{$baseModelNamespace}\\{$modelName}";

        // Create an alias for the base model import (e.g., "BasePlan")
        $baseModelAlias = "Base{$modelName}";

        // Generate the content for the stub file using Heredoc
        $stubContent = <<<PHP
        <?php

        namespace App\Models;

        use {$baseModelFqcn} as {$baseModelAlias};

        /**
         * Represents the application's version of the {$modelName} model.
         *
         * Extends the base model from the KaziSTM/Subscriptions package.
         * You can override properties, add relationships, or introduce custom logic here
         * without modifying the vendor package files.
         */
        class {$modelName} extends {$baseModelAlias}
        {
           
        }

        PHP;

        $this->filesystem->put($targetPath, $stubContent);
    }

    /**
     * Publishes migration files from a source to a target directory, adding timestamps.
     * Skips files if a migration with the same base name already exists.
     * Returns true if any files were published, false otherwise.
     */
    protected function publishMigrationsWithTimestamps(string $from, string $to, array $files): bool
    {
        $this->filesystem->ensureDirectoryExists($to);
        $published = false;

        foreach ($files as $index => $file) {
            // Check if a migration with this base name already exists in the target directory
            $existingMigration = collect($this->filesystem->glob("{$to}/*_{$file}.php"))->first();

            if ($existingMigration) {
                $this->line("  <fg=yellow>Skipped Migration:</> {$file}.php (already exists as " . basename($existingMigration) . ')');

                continue; // Skip to the next file
            }

            // If no existing migration, proceed to copy with a new timestamp
            $timestamp = now()->addSeconds($index)->format('Y_m_d_His');
            $source = "{$from}/{$file}.php";
            $target = "{$to}/{$timestamp}_{$file}.php";

            // Double-check source exists before copying
            if (! $this->filesystem->exists($source)) {
                $this->warn("  <fg=red>Source Missing:</> Migration source file not found at {$source}");

                continue;
            }

            $this->filesystem->copy($source, $target);
            $this->line("  <info>Published Migration:</info> {$timestamp}_{$file}.php");
            $published = true; // Mark that at least one migration was published

        }

        return $published;
    }

    /**
     * Get the full path to a file/directory within the package.
     */
    protected function packagePath(string $path = ''): string
    {
        // Assumes the command class is in src/Commands/
        // Adjust dirname level if your command class location is different
        return dirname(__DIR__, 2) . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }
}
