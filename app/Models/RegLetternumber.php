<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegLetternumber extends Model
{
    //
    protected $fillable = [
        'company_id',
        'fiscal',
        'letter_no',
        'letter_attentionto',
        'letter_title',
        'letter_date',
        'use_materai',
        'requester_id',
        'remark',
        'document_file',
        'original_names',
        'letter_tono'
    ];

    protected $casts = [
        'document_file' => 'array',
        'original_names' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            \Log::info('Model creating event:', [
                'user' => auth()->id(),
                'permission' => 'create_reg::letternumber',
                'has_permission' => auth()->user()->can('create_reg::letternumber')
            ]);
        });
    }    
}
