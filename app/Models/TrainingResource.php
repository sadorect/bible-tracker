<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class TrainingResource extends Model
{
    use HasFactory;

    public const TYPE_YOUTUBE = 'youtube';

    public const TYPE_PDF = 'pdf';

    public const TYPE_COMBINED = 'combined';

    protected $fillable = [
        'reading_plan_id',
        'title',
        'resource_type',
        'resource_url',
        'resource_path',
        'description',
        'sort_order',
    ];

    public function readingPlan(): BelongsTo
    {
        return $this->belongsTo(ReadingPlan::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(TrainingCompletion::class);
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->resource_url && $this->resource_path) {
            return 'YouTube + PDF';
        }

        if ($this->resource_path) {
            return 'PDF';
        }

        if ($this->resource_url) {
            return 'YouTube';
        }

        return match ($this->resource_type) {
            self::TYPE_PDF => 'PDF',
            self::TYPE_COMBINED => 'YouTube + PDF',
            default => 'YouTube',
        };
    }

    public static function resolveResourceType(?string $resourceUrl, ?string $resourcePath): string
    {
        if ($resourceUrl && $resourcePath) {
            return self::TYPE_COMBINED;
        }

        if ($resourcePath) {
            return self::TYPE_PDF;
        }

        return self::TYPE_YOUTUBE;
    }

    public function getVideoLinkAttribute(): ?string
    {
        return $this->resource_url;
    }

    public function getDocumentLinkAttribute(): ?string
    {
        if (! $this->resource_path) {
            return null;
        }

        return Storage::disk('public')->url($this->resource_path);
    }

    public function getResourceLinkAttribute(): ?string
    {
        return $this->video_link ?? $this->document_link;
    }
}
