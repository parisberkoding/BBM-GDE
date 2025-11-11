<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vehicles_healths', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->foreign('vehicle_id')->references('id')->on('vehicle_and_tools_consumers')->onDelete('restrict');
            
            $table->integer('last_odometer_reading');
            $table->date('recorded_last');
            
            $table->integer('latest_odometer_reading');
            $table->date('recorded_latest');

            $table->integer('sum_of_calculated_odoometer');
            
            $table->string('health_status');
            $table->integer('will_need_repair_in_odoometer')->nullable();

            $table->integer('repair_at_odoometer')->nullable();
            $table->date('repair_at_date')->nullable();
            

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles_healths');
    }
};
