<?php

namespace MediaPlatform\Configuration\LanguageModels\Models;

use Database\Factories\Media_platform\Configuration\Language_models\LanguageModelFactory;
use MediaPlatform\Configuration\Providers\Models\Provider;
use MediaPlatform\Configuration\UseCases\Models\UseCase;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LanguageModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
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
        static::creating(function (LanguageModel $model) {
            if (empty($model->slug)) {
                $model->slug = self::makeSlug($model->name);
            }
        });

        static::updating(function (LanguageModel $model) {
            if ($model->isDirty('name') && ! $model->isDirty('slug')) {
                $model->slug = self::makeSlug($model->name);
            }
        });
    }

    private static function makeSlug(string $value): string
    {
        return str_replace(' ', '-', strtolower(trim($value)));
    }

    // ---------------------------------------------------------------------------
    // Tell Laravel to stop guessing that the factory for this model 
    // is at "Database\Factories\Language_models\Models\LanguageModelFactory".
    // And, use this factory instead.
    // ---------------------------------------------------------------------------
    protected static function newFactory()
    {
        return LanguageModelFactory::new();
    }

    // ---------------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------------

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function useCases(): BelongsToMany
    {
        return $this->belongsToMany(UseCase::class, 'language_model_use_case');
    }

    // ---------------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('enabled', true);
    }
}
