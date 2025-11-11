<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permohonan extends Model
{
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

    // approved request relaton
    public function isApproved()
    {
        return $this->belongsTo(Approved_Request::class, 'id', 'permohonan_id');
    }
}