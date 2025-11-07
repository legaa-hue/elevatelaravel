<?php

namespace App\Traits;

trait HasVersioning
{
    /**
     * Boot the trait.
     */
    protected static function bootHasVersioning()
    {
        // Increment version on update
        static::updating(function ($model) {
            if ($model->isDirty() && !$model->isDirty('version')) {
                $model->version = ($model->version ?? 0) + 1;
            }
        });

        // Set initial version on create
        static::creating(function ($model) {
            if (!isset($model->version)) {
                $model->version = 1;
            }
        });
    }

    /**
     * Get the current version.
     *
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version ?? 1;
    }

    /**
     * Check if client version matches current version.
     *
     * @param int $clientVersion
     * @return bool
     */
    public function hasVersionConflict(int $clientVersion): bool
    {
        return $this->getVersion() !== $clientVersion;
    }
}
