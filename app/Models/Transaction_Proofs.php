<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction_Proofs extends Model
{

    use hasFactory, SoftDeletes;

    protected $table = 'transaction_proofs';  // Perbaiki nama tabel

    protected $fillable = [
        'transaction_id',
        'req_id',
        'requester_id',
        'voucher_number',
        'purchase_datetime',
        'fuel_volume',
        'km_terakhir',
        'struk_bbm_path',
        'odometer_photo_path',
    ];

    protected $casts = [
        'purchase_datetime' => 'datetime',
    ];

    // Relasi ke Permohonan
    public function permohonan()
    {
        return $this->belongsTo(Permohonan::class, 'req_id');
    }

    // Relasi ke User (Requester)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }
}
