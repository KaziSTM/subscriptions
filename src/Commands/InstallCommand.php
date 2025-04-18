<?php

namespace KaziSTM\Subscriptions\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'subscriptions:install';
    protected $description = 'Set up the subscriptions package (publish assets and create models)';

    public function handle()
    {
        $this->info('🔧 Publishing configuration and migrations...');
        $this->call('vendor:publish', [
            '--provider' => "KaziSTM\\Subscriptions\\SubscriptionServiceProvider",
            '--tag' => 'config',
        ]);
        $this->call('vendor:publish', [
            '--provider' => "KaziSTM\\Subscriptions\\SubscriptionServiceProvider",
            '--tag' => 'migrations',
        ]);

        $this->info('📦 Creating local model stubs...');
        $this->createModel('Plan');
        $this->createModel('Feature');
        $this->createModel('Limitation');
        $this->createModel('Subscription');
        $this->createModel('Usage');

        $this->info('✅ Subscriptions package is installed!');
    }

    protected function createModel(string $name): void
    {
        $namespace = "App\\Models";
        $path = app_path("Models/{$name}.php");

        if (File::exists($path)) {
            $this->line("⏩ {$name} already exists. Skipping.");
            return;
        }

        $stub = <<<PHP
<?php

namespace {$namespace};

use KaziSTM\Subscriptions\Models\\{$name} as Base{$name};

class {$name} extends Base{$name}
{
    //
}
PHP;

        File::ensureDirectoryExists(app_path('Models'));
        File::put($path, $stub);

        $this->line("✅ Created: app/Models/{$name}.php");
    }
}
