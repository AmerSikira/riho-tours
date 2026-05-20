<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        'contract_pdf_path',
        'contract_expires_at',
        'contract_access_signature_hash',
        'klijent_id',
        'ime_prezime',
        'email',
        'telefon',
        'broj_putnika',
        'status',
        'broj_fiskalnog_racuna',
        'placanje',
        'nacin_uplate',
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
            'contract_expires_at' => 'datetime',
        ];
    }

    /**
     * Assign sequential order number for new reservations.
     */
    protected static function booted(): void
    {
        static::creating(function (self $reservation): void {
            if (! $reservation->getKey()) {
                $reservation->{$reservation->getKeyName()} = (string) Str::uuid();
            }

            if ($reservation->order_num === null) {
                $reservation->order_num = (int) DB::table('reservation_order_sequences')
                    ->insertGetId([]);
            }

            if ($reservation->contract_access_signature_hash === null) {
                $createdAt = $reservation->created_at instanceof CarbonInterface
                    ? $reservation->created_at
                    : Carbon::now();

                if ($reservation->created_at === null) {
                    $reservation->created_at = $createdAt;
                }

                $reservation->contract_access_signature_hash = self::hashContractAccessSignature(
                    self::contractAccessSignatureFor((string) $reservation->getKey(), $createdAt)
                );
            }
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
     * Build a formatted document number for invoices and contracts.
     */
    public function documentNumber(?CarbonInterface $date = null): string
    {
        $year = ($date ?? now())->format('Y');
        $orderNumber = $this->order_num ?? 0;

        return sprintf('WEB-%d/%s', $orderNumber, $year);
    }

    /**
     * Build the stable raw contract access signature for this reservation.
     */
    public function contractAccessSignature(): string
    {
        $createdAt = $this->created_at instanceof CarbonInterface
            ? $this->created_at
            : Carbon::parse($this->created_at);

        return self::contractAccessSignatureFor((string) $this->getKey(), $createdAt);
    }

    /**
     * Build a stable contract access signature from reservation identity.
     */
    public static function contractAccessSignatureFor(string $reservationId, CarbonInterface $createdAt): string
    {
        return hash_hmac(
            'sha256',
            sprintf('%s|%s', $reservationId, $createdAt->toDateTimeString()),
            (string) config('app.key')
        );
    }

    /**
     * Hash the raw contract access signature before storage.
     */
    public static function hashContractAccessSignature(string $signature): string
    {
        return hash('sha256', $signature);
    }
}
