<?php

namespace Platform\Notifications;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

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
    }

    public function register(): void
    {
        // Config zusammenführen (Standardwerte + overrides)
        $this->mergeConfigFrom(
            __DIR__ . '/../config/notifications.php',
            'notifications'
        );
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