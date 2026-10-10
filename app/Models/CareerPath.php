<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CareerPath extends Model
{
    protected $fillable = [
        'user_id',
        'assessment_id',
        'name',
        'description',
        'reason',
        'match_percentage',
        'required_skills',
        'path_order',
        'status',
        'progress',
        'completed_at',
    ];

    protected $casts = [
        'required_skills' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function roadmapTasks()
    {
        return $this->hasMany(RoadmapTask::class);
    }

    public function recommendedCourses()
    {
        return $this->hasMany(RecommendedCourse::class);
    }
}