<?php

namespace Platform\Notifications\Traits;

use Illuminate\Database\Eloquent\Model;
use Platform\Notifications\Models\NotificationsNotice;

trait LogsNotices
{
    /**
     * Überschreibt (modellweit) die Events, die getrackt werden sollen.
     * Falls leer, wird auf config('notifications.events') zurückgegriffen.
     *
     * @var string[]
     */
    protected static array $recordEvents = [];

    /**
     * Modell-Instanzspezifische Attribute, die nicht getrackt werden sollen.
     * Merged mit config('notifications.ignore_attributes').
     *
     * @var string[]
     */
    protected array $ignoreAttributes = [];

    /**
     * Boot den LogsNotices-Trait und registriert Event-Listener.
     */
    public static function bootLogsNotices(): void
    {
        $events = static::$recordEvents ?: config('notifications.events', []);

        foreach ($events as $event) {
            static::{$event}(function (Model $model) use ($event) {
                $model->recordNotice($event);
            });
        }
    }

    /**
     * Polymorphe Beziehung zu allen Notices für dieses Modell.
     */
    public function notices()
    {
        return $this->morphMany(NotificationsNotice::class, 'noticable')->latest();
    }

    /**
     * Legt eine neue Notice für dieses Model an.
     */
    public function recordNotice(string $event, array $extra = []): void
    {
        $properties = $this->getNoticeProperties($event);

        // Bei Updates ohne echte Änderungen nichts anlegen
        if ($event === 'updated' && empty($properties)) {
            return;
        }

        $this->notices()->create(array_merge([
            'notice_type' => 'model_event',
            'title'       => ucfirst($event),
            'message'     => sprintf('%s wurde %s', class_basename($this), $event),
            'user_id'     => auth()->id(),
            'team_id'     => method_exists($this, 'team_id') ? $this->team_id : null,
            'properties'  => $properties,
            'metadata'    => [],
        ], $extra));
    }

    /**
     * Bequemer Alias für manuelle Benachrichtigungen (z. B. außerhalb von Events).
     */
    public function logNotice(string $title, string $message = '', array $extra = []): void
    {
        $this->notices()->create(array_merge([
            'notice_type' => 'manual',
            'title'       => $title,
            'message'     => $message,
            'user_id'     => auth()->id(),
            'team_id'     => method_exists($this, 'team_id') ? $this->team_id : null,
            'properties'  => [],
            'metadata'    => [],
        ], $extra));
    }

    /**
     * Bestimmt, welche Attribute als `properties` gespeichert werden sollen.
     */
    protected function getNoticeProperties(string $event): array
    {
        $attrs = $event === 'updated' ? $this->getChanges() : $this->getAttributes();
        $ignore = array_merge(config('notifications.ignore_attributes', []), $this->ignoreAttributes);

        return collect($attrs)->except($ignore)->toArray();
    }
}