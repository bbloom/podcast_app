<?php

namespace MediaPlatform\Tools\HealthChecks\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAlert extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    // ─── Scopes ──────────────────────────────────────────────

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false)->orderBy('tier', 'desc');
    }

    public function scopeBlockers($query, string $category)
    {
        return $query->where('is_resolved', false)
            ->where('tier', 3)
            ->where('category', $category);
    }

    public function scopeNeedsNotification($query)
    {
        return $query->where('is_resolved', false)
            ->whereIn('tier', [2, 3])
            ->whereNull('notified_at');
    }

    // ─── Actions ─────────────────────────────────────────────

    public function markResolved(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    public function autoResolve(): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    public function markNotified(): void
    {
        $this->update(['notified_at' => now()]);
    }

    // ─── Factory ─────────────────────────────────────────────

    /**
     * Create an alert if one doesn't already exist (unresolved) for this category+title.
     * Prevents duplicate alerts for the same ongoing issue.
     */
    public static function raiseIfNew(int $tier, string $category, string $title, string $message): ?self
    {
        $existing = self::where('category', $category)
            ->where('title', $title)
            ->where('is_resolved', false)
            ->first();

        if ($existing) {
            return null;
        }

        return self::create([
            'tier'     => $tier,
            'category' => $category,
            'title'    => $title,
            'message'  => $message,
        ]);
    }
}
