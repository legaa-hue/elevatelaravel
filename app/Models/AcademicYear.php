<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = [
        'year_name',
        'file_path',
        'file_name',
        'version',
        'status',
        'notes',
        'uploaded_by',
    ];

    /**
     * Get the user who uploaded this academic year.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the active academic year.
     */
    public static function getActive()
    {
        return self::where('status', 'Active')->first();
    }

    /**
     * Get version history for a specific year.
     */
    public static function getVersionHistory($yearName)
    {
        return self::where('year_name', $yearName)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Calculate next version number for a year.
     */
    public static function getNextVersion($yearName)
    {
        $latest = self::where('year_name', $yearName)
            ->orderBy('version', 'desc')
            ->first();

        if (!$latest) {
            return 'v1.0';
        }

        // Parse version (e.g., "v1.2" -> 1.2)
        $version = floatval(str_replace('v', '', $latest->version));
        $nextVersion = $version + 0.1;

        return 'v' . number_format($nextVersion, 1);
    }
}
