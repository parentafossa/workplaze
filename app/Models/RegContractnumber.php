<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegContractnumber extends Model
{
    protected $fillable = [
        'company_id',
        'fiscal',
        'header_no',
        'from_no',
        'to_no',
        'use_materai',
        'business_area',
        'requester_id',
        'request_date',
        'remark',
        'document_file',
        'original_names'
    ];    //

    protected $casts = [
        'document_file' => 'array',
        'original_names' => 'array',
    ];
}
