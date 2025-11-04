<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'academic_year_id',
        'program_id',
        'course_template_id',
        'title',
        'section',
        'description',
        'units',
        'gradebook',
        'join_code',
        'status',
    ];

    protected $casts = [
        'gradebook' => 'array',
    ];

    protected $attributes = [
        'status' => 'Pending', // Courses require admin approval before becoming active
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($course) {
            if (empty($course->join_code)) {
                $course->join_code = strtoupper(Str::random(6));
            }
        });
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Alias for teacher relationship (used in some controllers)
    public function user()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function joinedCourses()
    {
        return $this->hasMany(JoinedCourse::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'joined_courses', 'course_id', 'user_id')
                    ->wherePivot('role', 'Student')
                    ->withTimestamps();
    }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'joined_courses', 'course_id', 'user_id')
                    ->wherePivot('role', 'Teacher')
                    ->withTimestamps();
    }

    public function classworks()
    {
        return $this->hasMany(Classwork::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function courseTemplate()
    {
        return $this->belongsTo(CourseTemplate::class);
    }
}
