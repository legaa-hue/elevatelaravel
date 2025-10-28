<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * Get the course templates for this program.
     */
    public function courseTemplates()
    {
        return $this->hasMany(CourseTemplate::class);
    }

    /**
     * Get the courses for this program.
     */
    public function courses()
    {
        return $this->hasMany(Course::class);
    }
}
