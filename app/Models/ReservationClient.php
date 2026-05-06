<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReservationClient extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'reservation_clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rezervacija_id',
        'klijent_id',
        'paket_id',
        'ime_na_predracunu_racunu',
        'dodatno_na_cijenu',
        'popust',
        'boravisna_taksa',
        'osiguranje',
        'doplata_jednokrevetna_soba',
        'doplata_dodatno_sjediste',
        'doplata_sjediste_po_zelji',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ime_na_predracunu_racunu' => 'boolean',
        'dodatno_na_cijenu' => 'decimal:2',
        'popust' => 'decimal:2',
        'boravisna_taksa' => 'decimal:2',
        'osiguranje' => 'decimal:2',
        'doplata_jednokrevetna_soba' => 'decimal:2',
        'doplata_dodatno_sjediste' => 'decimal:2',
        'doplata_sjediste_po_zelji' => 'decimal:2',
    ];

    /**
     * Reservation parent.
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'rezervacija_id');
    }

    /**
     * Client on reservation.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'klijent_id');
    }

    /**
     * Selected package for client.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ArrangementPackage::class, 'paket_id');
    }
}
