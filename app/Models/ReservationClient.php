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
        'dodatno_na_cijenu',
        'popust',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'dodatno_na_cijenu' => 'decimal:2',
        'popust' => 'decimal:2',
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
