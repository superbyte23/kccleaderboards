<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasUuids, SoftDeletes;

    // fillable properties
    protected $fillable = [
        'name',
        'description',
        'event_date',
    ];

    protected $casts = [
        'event_date' => 'date'
    ];

    protected static function booted()
    {
        static::deleting(function ($event) {
            // We use each->delete() to ensure the 'deleting' 
            // hooks in Team and Competition models are also triggered.
            $event->teams->each->delete();
            $event->competitions->each->delete();
        });

        static::restoring(function ($event) {
            // This brings back the teams and competitions
            $event->teams()->withTrashed()->restore();
            $event->competitions()->withTrashed()->restore();
        });
    }

    // relationships
    public function competitions()
    {        
        return $this->hasMany(Competition::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
