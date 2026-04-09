<?php

namespace MediaPlatform\API\v1\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'api_clients';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'label',
        'domain',
        'token_hash',
        'is_active',
        'last_used_at',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Token management
    // -------------------------------------------------------------------------

    /**
     * Generate a new plain-text token, store its hash, and return the
     * plain-text token. This is the only moment the plain-text token exists —
     * it is never stored and cannot be retrieved again.
     */
    public function generateToken(): string
    {
        $plainText = Str::random(64);

        $this->update(['token_hash' => Hash::make($plainText)]);

        return $plainText;
    }

    /**
     * Verify a plain-text bearer token against this client's stored hash.
     */
    public function verifyToken(string $plainText): bool
    {
        return Hash::check($plainText, $this->token_hash);
    }

    /**
     * Record the current timestamp as the last successful request time.
     */
    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    // -------------------------------------------------------------------------
    // Lookups
    // -------------------------------------------------------------------------

    /**
     * Find an active client by domain name.
     * Returns null if no active client exists for the given domain.
     */
    public static function findActiveByDomain(string $domain): ?static
    {
        return static::where('domain', $domain)
            ->where('is_active', true)
            ->first();
    }
}