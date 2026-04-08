<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArrangementPackage extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'arrangement_packages';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'aranzman_id',
        'naziv',
        'opis',
        'cijena',
        'smjestaj_trosak',
        'transport_trosak',
        'fakultativne_stvari_trosak',
        'ostalo_trosak',
        'is_active',
        'created_by',
        'updated_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'cijena' => 'decimal:2',
            'smjestaj_trosak' => 'decimal:2',
            'transport_trosak' => 'decimal:2',
            'fakultativne_stvari_trosak' => 'decimal:2',
            'ostalo_trosak' => 'decimal:2',
        ];
    }

    /**
     * Get arrangement owning this package.
     */
    public function arrangement(): BelongsTo
    {
        return $this->belongsTo(Arrangement::class, 'aranzman_id');
    }

    /**
     * Reservation rows using this package.
     */
    public function reservationClients(): HasMany
    {
        return $this->hasMany(ReservationClient::class, 'paket_id');
    }
}
