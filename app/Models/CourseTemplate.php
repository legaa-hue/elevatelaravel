<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'course_code',
        'course_name',
        'course_type',
        'units',
        'status',
    ];

    /**
     * Get the program that owns the course template.
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the courses created from this template.
     */
    public function courses()
    {
        return $this->hasMany(Course::class, 'course_template_id');
    }
}
