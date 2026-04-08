<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'company_name',
        'company_id',
        'maticni_broj_subjekta_upisa',
        'pdv',
        'trn',
        'banka',
        'iban',
        'swift',
        'osiguravajuce_drustvo',
        'email',
        'phone',
        'address',
        'city',
        'zip',
        'created_by',
        'updated_by',
    ];
}
