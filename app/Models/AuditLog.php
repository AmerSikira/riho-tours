<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AuditLog extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event',
        'auditable_type',
        'auditable_id',
        'causer_type',
        'causer_id',
        'old_values',
        'new_values',
        'request_context',
        'created_by',
        'updated_by',
    ];

    /**
     * Cast model attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'request_context' => 'array',
        ];
    }

    /**
     * Related changed model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'auditable_type', 'auditable_id');
    }

    /**
     * Related actor model.
     */
    public function causer(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'causer_type', 'causer_id');
    }
}
