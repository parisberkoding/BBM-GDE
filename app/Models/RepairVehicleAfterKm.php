<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairVehicleAfterKm extends Model
{
    protected $table = 'repair_vehicle_after_km';

    protected $fillable = [
        'repairment_code',
        'vehicle_id',
        'repairment_description',
        'repairment_due_date',
        'PIC_id',
    ];
}
