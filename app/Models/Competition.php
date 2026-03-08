<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Competition extends Model
{
    use HasUuids, SoftDeletes;

    // fillable properties
    protected $fillable = [
        'name',
        'category',
        'event_id',
    ];

    protected static function booted()
    {
        static::deleting(function ($competition) {
            // This handles the "cascade" for Soft Deletes
            $competition->results()->delete(); 
        });

        static::restoring(function ($competition) {
            // Optional: If you restore a comp, restore its results too!
            $competition->results()->restore();
        });
    }

    // relationships
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }

    public function teams()
    {
        return $this->event->teams();
    }
}
