<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasUuids, SoftDeletes;

    // fillable properties
    protected $fillable = [
        'event_id',
        'name',
        'avatar',
        'represents',
        'color'
       
    ];

    protected static function booted()
    {
        static::deleting(function ($team) {
            $team->results()->delete();
        });

        static::restoring(function ($team) {
            $team->results()->withTrashed()->restore();
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
}
