<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'm_vendors';
    
    protected $keyType = 'string';
    
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'vendor_hold',
        'telephone',
        'extension',
        'primary_contact',
        'group',
        'currency',
        'address',
        'city',
        'payment_term',
        'email_address'
    ];

    protected $casts = [
        'city' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'currency' => 'IDR',
    ];
}