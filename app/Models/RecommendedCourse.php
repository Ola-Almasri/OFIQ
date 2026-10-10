<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecommendedCourse extends Model
{
    protected $fillable = [
        'user_id',
        'career_path_id',
        'title',
        'platform',
        'level',
        'url',
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