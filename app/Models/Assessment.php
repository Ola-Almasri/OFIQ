<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assessment extends Model
{
protected $fillable = [
    'user_id',
    'phone',
    'birth_date',
    'gender',
    'country',
    'city',
    'education_level',
    'major',
    'university',
    'gpa',
    'grading_system',
    'graduation_year',
    'skills',
    'english_level',
    'experience',     
    'interests',
    'goal',          
    'work_environment',
];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
}