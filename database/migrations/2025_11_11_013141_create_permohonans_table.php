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
        Schema::create('permohonans', function (Blueprint $table) {
            $table->id();
            
            // request number unique
            $table->string('request_number')->unique();

            // foreign key to users table, requester data
            $table->unsignedBigInteger('requester_id');
            $table->foreign('requester_id')->references('id')->on('users')->onDelete('restrict');

            // request details
            $table->timestamp('request_date')->useCurrent();
            $table->string('gasoline_type');
            $table->float('bill_amounts');
            $table->string('status')->default('pending');
            $table->text('requester_notes')->nullable();

            // authorizer details 
            $table->unsignedBigInteger('authorizer_id')->nullable();
            $table->foreign('authorizer_id')->references('id')->on('users')->onDelete('restrict');
            $table->timestamp('authorization_date')->nullable();
            $table->text('authorizer_notes')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permohonans');
    }
};
