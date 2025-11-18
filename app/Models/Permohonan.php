<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permohonan extends Model
{
    use HasFactory;

    protected $table = 'permohonans';

    protected $fillable = [
        'request_number',
        'requester_id',
        'request_date',
        'gasoline_type',
        'bill_amounts',
        'status',
        'requester_notes',
        'authorizer_id',
        'authorization_date',
        'authorizer_notes',
        'consumerial_tools_id'
    ];

    protected $casts = [
        'request_date' => 'datetime',
        'authorization_date' => 'datetime',
    ];

    // Relasi ke User (Requester)
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    // Relasi ke User (Authorizer)
    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorizer_id');
    }

    // Relasi ke VehicleAndToolsConsumers
    public function vehicle()
    {
        return $this->belongsTo(VehicleAndToolsConsumers::class, 'consumerial_tools_id');
    }

    // Relasi ke ApprovedRequest
    public function approvedRequest()
    {
        return $this->hasOne(Approved_Request::class, 'permohonan_id');
    }

    // Relasi ke TransactionProof
    public function transactionProof()
    {
        return $this->hasOne(Transaction_Proofs::class, 'req_id');
    }

    // relasi gasoline type
    public function gasolineType()
    {
        return $this->belongsTo(GasolineType::class, 'gasoline_type');
    }

    // Scope untuk filter status
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
