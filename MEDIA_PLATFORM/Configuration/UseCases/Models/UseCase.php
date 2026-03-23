<?php

namespace MediaPlatform\Configuration\UseCases\Models;

use Database\Factories\Media_platform\Configuration\Language_models\UseCaseFactory;
use MediaPlatform\Configuration\LanguageModels\Models\LanguageModel;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UseCase extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];


    // ---------------------------------------------------------------------------
    // Boot
    // ---------------------------------------------------------------------------

    protected static function booted(): void
    {
        static::creating(function (UseCase $useCase) {
            if (empty($useCase->slug)) {
                $useCase->slug = self::makeSlug($useCase->name);
            }
        });

        static::updating(function (UseCase $useCase) {
            if ($useCase->isDirty('name') && ! $useCase->isDirty('slug')) {
                $useCase->slug = self::makeSlug($useCase->name);
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
        return UseCaseFactory::new();
    }

    // ---------------------------------------------------------------------------
    // Relations
    // ---------------------------------------------------------------------------

    public function languageModels(): BelongsToMany
    {
        return $this->belongsToMany(LanguageModel::class, 'language_model_use_case');
    }
}
