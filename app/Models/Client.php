<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ime',
        'prezime',
        'broj_dokumenta',
        'datum_rodjenja',
        'adresa',
        'broj_telefona',
        'email',
        'fotografija_putanja',
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
            'datum_rodjenja' => 'date',
        ];
    }

    /**
     * All reservations for this client.
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'klijent_id');
    }

    /**
     * Reservation-client package rows.
     */
    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationClient::class, 'klijent_id');
    }
}
