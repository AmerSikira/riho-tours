<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebReservation extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'web_reservations';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'aranzman_id',
        'paket_id',
        'converted_reservation_id',
        'ime',
        'prezime',
        'email',
        'broj_telefona',
        'adresa',
        'broj_putnika',
        'napomena',
        'source_domain',
        'source_url',
        'landing_page_url',
        'referrer_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'payload',
        'status',
        'converted_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'broj_putnika' => 'integer',
            'payload' => 'array',
            'converted_at' => 'datetime',
        ];
    }

    public function arrangement(): BelongsTo
    {
        return $this->belongsTo(Arrangement::class, 'aranzman_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(ArrangementPackage::class, 'paket_id');
    }

    public function convertedReservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'converted_reservation_id');
    }
}
