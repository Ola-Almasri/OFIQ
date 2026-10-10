<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadmapTask extends Model
{
    protected $fillable = [
        'user_id',
        'career_path_id',
        'month_number',
        'week_number',
        'day_number',
        'title',
        'description',
        'task_type',
        'duration',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function careerPath()
    {
        return $this->belongsTo(CareerPath::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}