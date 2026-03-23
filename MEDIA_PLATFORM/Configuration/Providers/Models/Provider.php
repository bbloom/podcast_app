<?php

namespace MediaPlatform\Configuration\Providers\Models;

use Database\Factories\Media_platform\Configuration\Language_models\ProviderFactory;
use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'website_url',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // ---------------------------------------------------------------------------
    // Boot
    // ---------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (Provider $provider) {
            if (empty($provider->slug)) {
                $provider->slug = self::makeSlug($provider->name);
            }
        });

        static::updating(function (Provider $provider) {
            if ($provider->isDirty('name') && ! $provider->isDirty('slug')) {
                $provider->slug = self::makeSlug($provider->name);
            }
        });
    }

    private static function makeSlug(string $value): string
    {
        return str_replace(' ', '-', strtolower(trim($value)));
    }

    // ---------------------------------------------------------------------------
    // Tell Laravel to stop guessing that the factory for this model 
    // is at "Database\Factories\Language_models\Models\ProviderModelFactory".
    // And, use this factory instead.
    // ---------------------------------------------------------------------------
    protected static function newFactory()
    {
        return ProviderFactory::new();
    }


    // ---------------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------------

    public function languageModels(): HasMany
    {
        return $this->hasMany(LanguageModel::class);
    }

    // ---------------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('enabled', true);
    }
}
