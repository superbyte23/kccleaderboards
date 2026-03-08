<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Result extends Model
{
    use HasUuids, SoftDeletes;

    // fillable properties
    protected $fillable = [
        'team_id',
        'competition_id',
        'score',
    ];

    // relationships
    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }
}
