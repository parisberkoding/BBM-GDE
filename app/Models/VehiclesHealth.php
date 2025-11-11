<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiclesHealth extends Model
{
    use HasFactory, SoftDeletes;

   protected $table = 'vehicles_healths';

    protected $fillable = [
          'vehicle_id',
          'last_odometer_reading',
          'recorded_last',
          'latest_odometer_reading',
          'recorded_latest',
          'sum_of_calculated_odoometer',
          'health_status',
          'will_need_repair_in_odoometer',
          'repair_at_odoometer',
          'repair_at_date',
     ];

     // relasi ke VehicleAndToolsConsumers
        public function vehicle()
        {
            return $this->belongsTo(VehicleAndToolsConsumers::class, 'vehicle_id');
        }

    
}