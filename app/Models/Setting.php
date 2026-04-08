<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'invoice_prefix',
        'company_id',
        'maticni_broj_subjekta_upisa',
        'pdv',
        'u_pdv_sistemu',
        'trn',
        'broj_kase',
        'banka',
        'iban',
        'swift',
        'osiguravajuce_drustvo',
        'polisa_osiguranja',
        'email',
        'phone',
        'address',
        'city',
        'zip',
        'logo_path',
        'potpis_path',
        'pecat_path',
    ];

    /**
     * Cast attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'u_pdv_sistemu' => 'boolean',
        ];
    }
}
