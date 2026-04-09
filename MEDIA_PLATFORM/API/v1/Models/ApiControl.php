<?php

namespace MediaPlatform\API\v1\Models;

use Illuminate\Database\Eloquent\Model;

class ApiControl extends Model
{
    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'api_controls';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'is_enabled',
        'enabled_at',
        'disabled_at',
        'notes',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'is_enabled'  => 'boolean',
        'enabled_at'  => 'datetime',
        'disabled_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Return the single api_controls row, creating it if it does not exist.
     * The API is disabled by default.
     */
    public static function instance(): static
    {
        return static::firstOrCreate([], ['is_enabled' => false]);
    }

    /**
     * Enable the API and record the timestamp.
     */
    public function enable(): void
    {
        $this->update([
            'is_enabled' => true,
            'enabled_at' => now(),
        ]);
    }

    /**
     * Disable the API and record the timestamp.
     */
    public function disable(): void
    {
        $this->update([
            'is_enabled'  => false,
            'disabled_at' => now(),
        ]);
    }
}