<?php

namespace Platform\Notifications;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Platform\Notifications\Channels\DatabaseChannel;
use Platform\Notifications\Channels\PushoverChannel;
use Platform\Notifications\Channels\TeamsWebhookChannel;

class NotificationsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Log::info('Livewire component registered', [
            'exists' => class_exists(\Platform\Notifications\Http\Livewire\Notices\Index::class)
        ]);


        // Konfigurationsdatei veröffentlichen
        $this->publishes([
            __DIR__ . '/../config/notifications.php' => config_path('notifications.php'),
        ], 'config');

        // Migrationen laden & publishen
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'migrations');

        // Views laden & publishen
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'notifications');
        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/notifications'),
        ], 'views');

        // Alle Livewire-Komponenten (inkl. Unterordner) registrieren
        $this->registerLivewireComponents();

        // Notification Channels registrieren
        $this->registerNotificationChannels();

        // LLM-Tools registrieren
        $this->registerTools();
    }

    public function register(): void
    {
        // Config zusammenführen (Standardwerte + overrides)
        $this->mergeConfigFrom(
            __DIR__ . '/../config/notifications.php',
            'notifications'
        );

        $this->app->singleton(NotificationDispatcher::class);
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            $registry->register(new \Platform\Notifications\Tools\ListNoticesTool());
            $registry->register(new \Platform\Notifications\Tools\ListNotificationTypesTool());
            $registry->register(new \Platform\Notifications\Tools\ListNotificationChannelsTool());
            $registry->register(new \Platform\Notifications\Tools\ListNotificationPreferencesTool());
        } catch (\Throwable $e) {
            // Silent fail - ToolRegistry möglicherweise nicht verfügbar
        }
    }

    protected function registerNotificationChannels(): void
    {
        NotificationChannelRegistry::register(new DatabaseChannel());
        NotificationChannelRegistry::register(new PushoverChannel());
        NotificationChannelRegistry::register(new TeamsWebhookChannel());
    }

    /**
     * Registriert alle Livewire-Komponenten im Package,
     * inklusive Unterordner, automatisch.
     */

    protected function registerLivewireComponents(): void
    {
        $componentPath = __DIR__ . '/Http/Livewire';
        $namespace = 'Platform\\Notifications\\Http\\Livewire';
        $prefix = 'notifications';

        if (!is_dir($componentPath)) {
            Log::warning("[Notifications] Kein Livewire-Ordner gefunden: {$componentPath}");
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($componentPath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) {
                continue;
            }

            // Relativen Pfad ermitteln und führenden Backslash entfernen
            $relativePath = ltrim(str_replace([$componentPath, '/', '.php'], ['', '\\', ''], $file->getPathname()), '\\');

            $class = $namespace . '\\' . $relativePath;

            // Alias korrekt generieren
            $aliasPath = str_replace('\\', '.', $relativePath);
            $segments = explode('.', $aliasPath);
            $segments = array_map(fn($s) => Str::kebab($s), $segments);
            $alias = $prefix . '.' . implode('.', $segments);

            Log::info('[Notifications] Livewire-Check', [
                'file' => $file->getPathname(),
                'relativePath' => $relativePath,
                'class' => $class,
                'alias' => $alias,
                'exists' => class_exists($class),
            ]);

            if (class_exists($class)) {
                Livewire::component($alias, $class);
            } else {
                Log::warning("[Notifications] Klasse nicht gefunden: {$class}");
            }
        }
    }
}