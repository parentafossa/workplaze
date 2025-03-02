<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class D365VoucherTransaction extends Model
{
    protected $table = 'd365_voucher_transactions';
    protected $guarded = [];
    
    protected $casts = [
        'date' => 'datetime',
        'created_date_and_time' => 'datetime',
        'amount' => 'decimal:2',
        'amount_in_transaction_currency' => 'decimal:2',
        'amount_in_reporting_currency' => 'decimal:2',
        'year_closed' => 'boolean',
        'correction' => 'boolean',
        'crediting' => 'boolean',
        'level' => 'integer'
    ];
}
