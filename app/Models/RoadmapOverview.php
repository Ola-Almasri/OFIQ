<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapOverview extends Model
{
    protected $fillable = [
        'user_id',
        'career_path_id',
        'title',
        'summary',
        'stages',
        'skills',
        'outcomes',
        'estimated_duration',
        'level',
    ];

    protected $casts = [
        'stages' => 'array',
        'skills' => 'array',
        'outcomes' => 'array',
    ];
}