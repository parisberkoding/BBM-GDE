<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class VehicleAndToolsConsumers extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'vehicle_and_tools_consumers';

    protected $fillable = [
        'consumerial_type',
        'consumerial_code',
        'consumerial_name',
        'consumerial_notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relasi ke User (pembuat data)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke Permohonan (one to many)
     * Satu kendaraan/alat bisa punya banyak permohonan
     */
    public function permohonans()
    {
        return $this->hasMany(Permohonan::class, 'consumerial_tools_id');
    }

    /**
     * Scope: Filter berdasarkan tipe
     */
    public function scopeByType($query, $type)
    {
        return $query->where('consumerial_type', $type);
    }

    /**
     * Scope: Cari berdasarkan kode atau nama
     */
    public function scopeSearch($query, $keyword)
    {
        return $query->where(function($q) use ($keyword) {
            $q->where('consumerial_code', 'like', "%{$keyword}%")
              ->orWhere('consumerial_name', 'like', "%{$keyword}%");
        });
    }
}
