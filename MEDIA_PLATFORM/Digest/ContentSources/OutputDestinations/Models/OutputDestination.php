<?php

namespace MediaPlatform\Digest\ContentSources\OutputDestinations\Models;

use Database\Factories\Media_platform\Digest\Lists\OutputDestinationFactory;
use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OutputDestination — where a digest is delivered.
 *
 * Currently supports one destination type:
 *   - sftp : uploads a rendered HTML file to a remote server via SFTP
 *
 * Sensitive fields (password, private_key, passphrase) use Laravel's
 * `encrypted` cast so they are never stored in plain text.
 */
class OutputDestination extends Model
{
    use HasFactory;

    protected $table = 'output_destinations';

    protected $fillable = [
        'user_id',
        'name',
        'type',
        // ── SFTP fields ───────────────────────────────────────────────────────
        'host',
        'port',
        'username',
        'auth_type',
        'password',
        'private_key',
        'passphrase',
        'path',
        'base_url',
        // ── Shared ───────────────────────────────────────────────────────────
        'enabled',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        // SFTP sensitive fields — stored encrypted at rest
        'password'    => 'encrypted',
        'private_key' => 'encrypted',
        'passphrase'  => 'encrypted',
    ];

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    protected static function newFactory()
    {
        return OutputDestinationFactory::new();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this destination.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * All lists that use this output destination.
     */
    public function lists(): HasMany
    {
        return $this->hasMany(ListModel::class, 'output_destination_id');
    }
}