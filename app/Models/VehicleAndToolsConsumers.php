<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    // relasi ke User (pembuat data)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
