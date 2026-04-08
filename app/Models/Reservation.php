<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'reservations';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_num',
        'aranzman_id',
        'contract_template_id',
        'klijent_id',
        'ime_prezime',
        'email',
        'telefon',
        'broj_putnika',
        'status',
        'broj_fiskalnog_racuna',
        'placanje',
        'broj_rata',
        'rate',
        'napomena',
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
            'order_num' => 'integer',
            'broj_putnika' => 'integer',
            'broj_rata' => 'integer',
            'rate' => 'array',
        ];
    }

    /**
     * Assign sequential order number for new reservations.
     */
    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            if ($reservation->order_num !== null) {
                return;
            }

            $reservation->order_num = (int) DB::table('reservation_order_sequences')
                ->insertGetId([]);
        });
    }

    /**
     * Arrangement linked to this reservation.
     */
    public function arrangement(): BelongsTo
    {
        return $this->belongsTo(Arrangement::class, 'aranzman_id');
    }

    /**
     * Client linked to reservation.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'klijent_id');
    }

    /**
     * Contract template currently assigned to reservation.
     */
    public function contractTemplate(): BelongsTo
    {
        return $this->belongsTo(ContractTemplate::class, 'contract_template_id');
    }

    /**
     * Reservation client rows with selected package.
     */
    public function reservationClients(): HasMany
    {
        return $this->hasMany(ReservationClient::class, 'rezervacija_id');
    }

    /**
     * All generated contracts for this reservation.
     */
    public function generatedContracts(): HasMany
    {
        return $this->hasMany(GeneratedContract::class, 'reservation_id');
    }

    /**
     * Build a formatted document number for invoices and contracts.
     */
    public function documentNumber(?CarbonInterface $date = null): string
    {
        $year = ($date ?? now())->format('Y');
        $orderNumber = $this->order_num ?? 0;

        return sprintf('WEB-%d/%s', $orderNumber, $year);
    }
}
