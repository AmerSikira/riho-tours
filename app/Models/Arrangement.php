<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Arrangement extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'arrangements';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'sifra',
        'destinacija',
        'naziv_putovanja',
        'opis_putovanja',
        'plan_putovanja',
        'datum_polaska',
        'datum_povratka',
        'trajanje_dana',
        'tip_prevoza',
        'tip_smjestaja',
        'napomena',
        'is_active',
        'subagentski_aranzman',
        'supplier_id',
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
            'datum_polaska' => 'date',
            'datum_povratka' => 'date',
            'is_active' => 'boolean',
            'subagentski_aranzman' => 'boolean',
        ];
    }

    /**
     * Get all packages linked to this arrangement.
     */
    public function packages(): HasMany
    {
        return $this->hasMany(ArrangementPackage::class, 'aranzman_id');
    }

    /**
     * Get all images linked to this arrangement.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ArrangementImage::class, 'aranzman_id');
    }

    /**
     * Get all reservations linked to this arrangement.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'aranzman_id');
    }

    /**
     * Get all web reservations linked to this arrangement.
     */
    public function webReservations(): HasMany
    {
        return $this->hasMany(WebReservation::class, 'aranzman_id');
    }

    /**
     * Supplier linked to this arrangement when subagent mode is enabled.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
