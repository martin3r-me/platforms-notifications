<?php

namespace Platform\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class NotificationsNotice extends Model
{
    /**
     * Die Attribute, die massenweise befüllbar sind.
     *
     * @var string[]
     */
    protected $fillable = [
        'uuid',
        'notice_type',
        'title',
        'message',
        'description',
        'properties',
        'metadata',
        'user_id',
        'team_id',
        'noticable_type',  // <- polymorphe Relation (Modellklasse)
        'noticable_id',    // <- polymorphe Relation (Primärschlüssel)
        'read_at',
        'dismissed',
    ];

    /**
     * Automatische Casts für JSON und Datumsfelder.
     *
     * @var array
     */
    protected $casts = [
        'properties' => 'array',
        'metadata'   => 'array',
        'read_at'    => 'datetime',
        'dismissed'  => 'boolean',
    ];

    /**
     * Automatisch eine UUID v7 setzen, wenn neu erstellt.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = UuidV7::generate();
            }
        });
    }

    /**
     * Polymorphe Beziehung zum verknüpften Modell (z. B. Order, Project).
     */
    public function noticable()
    {
        return $this->morphTo();
    }

    /**
     * Zugehöriger Benutzer (Empfänger).
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Zugehöriges Team (Empfänger).
     */
    public function team()
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class);
    }

    /**
     * Scope: Nur ungelesene Notices.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at')->where('dismissed', false);
    }

    /**
     * Scope: Nur aktive (nicht gelöschte, nicht archivierte) Notices.
     */
    public function scopeActive($query)
    {
        return $query->where('dismissed', false);
    }
}