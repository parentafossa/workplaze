<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'm_customers';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'id',
        'name',
        'group',
        'currency',
        'address',
        'city',
        'payment_term',
        'contact_name',
        'email_address',
        'telephone'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'city' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'currency' => 'IDR',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(CustomerDocument::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(SalesOpportunity::class);
    }
}