<?php

namespace App\Models;

use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory, HasVersioning;

    protected $fillable = [
        'name',
        'description',
        'status',
        'version',
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
