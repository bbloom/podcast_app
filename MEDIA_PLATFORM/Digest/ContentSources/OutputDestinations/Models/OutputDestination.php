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
 * Currently supports two destination types:
 *   - sftp      : uploads a rendered HTML file to a remote server via SFTP
 *   - wordpress : publishes a post via the WordPress REST API
 *
 * The `type` column determines which columns are populated.
 * SFTP columns: host, port, username, auth_type, password, private_key,
 *               passphrase, path, base_url
 * WordPress columns: wordpress_url, wordpress_username, wordpress_app_password,
 *                    wordpress_post_status, wordpress_category_ids, wordpress_tag_ids
 *
 * Sensitive fields (password, private_key, passphrase, wordpress_app_password)
 * use Laravel's `encrypted` cast so they are never stored in plain text.
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
        // ── WordPress fields ──────────────────────────────────────────────────
        'wordpress_url',
        'wordpress_username',
        'wordpress_app_password',
        'wordpress_post_status',
        'wordpress_category_ids',
        'wordpress_tag_ids',
        // ── Shared ───────────────────────────────────────────────────────────
        'enabled',
    ];

    protected $casts = [
        'enabled'     => 'boolean',
        // SFTP sensitive fields — stored encrypted at rest
        'password'    => 'encrypted',
        'private_key' => 'encrypted',
        'passphrase'  => 'encrypted',
        // WordPress sensitive field — stored encrypted at rest
        'wordpress_app_password' => 'encrypted',
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

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse the comma-separated wordpress_category_ids string into an integer array.
     * Returns an empty array if the column is blank.
     * Used when building the WordPress REST API payload.
     */
    public function wordpressCategoryIdsArray(): array
    {
        if (empty($this->wordpress_category_ids)) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', explode(',', $this->wordpress_category_ids))
        ));
    }

    /**
     * Parse the comma-separated wordpress_tag_ids string into an integer array.
     * Returns an empty array if the column is blank.
     * Used when building the WordPress REST API payload.
     */
    public function wordpressTagIdsArray(): array
    {
        if (empty($this->wordpress_tag_ids)) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', explode(',', $this->wordpress_tag_ids))
        ));
    }
}