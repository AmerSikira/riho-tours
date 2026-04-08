<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContractTemplate extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'template_key',
        'version',
        'name',
        'description',
        'html_template',
        'placeholder_hints_json',
        'is_active',
        'subagentski_ugovor',
        'previous_version_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Cast attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'placeholder_hints_json' => 'array',
            'is_active' => 'boolean',
            'subagentski_ugovor' => 'boolean',
        ];
    }

    /**
     * Scope only active templates.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Previous template version.
     */
    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    /**
     * Next template versions.
     */
    public function nextVersions(): HasMany
    {
        return $this->hasMany(self::class, 'previous_version_id');
    }

    /**
     * Contracts generated from this template.
     */
    public function generatedContracts(): HasMany
    {
        return $this->hasMany(GeneratedContract::class, 'contract_template_id');
    }

    /**
     * Reservations currently linked to this template.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'contract_template_id');
    }

    /**
     * Resolve next version number for a logical template key.
     */
    public static function nextVersionFor(string $templateKey): int
    {
        $latestVersion = self::query()
            ->where('template_key', $templateKey)
            ->max('version');

        return ((int) $latestVersion) + 1;
    }
}
