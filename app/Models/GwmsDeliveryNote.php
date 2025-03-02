<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GwmsDeliveryNote extends Model
{
    protected static ?string $pluralModelLabel = 'GWMS D/Os';
    protected static ?string $ModelLabel = 'GWMS D/O';
    protected $fillable = [
        'system_id',
        'site_cd',
        'site_name',
        'owner_cd',
        'owner_name',
        'st_no',
        'status',
        'etd',
        'eta',
        'ship_to_cd',
        'ship_to_name',
        'ship_to_adr1',
        'ship_to_adr2',
        'sj_barcode',
        'truck_cd',
        'truck_name',
        'truck_no',
        'ctn_qty',
        'bulk_m3',
        'wgt_kg',
        'sj_receipt_print_flg',
        'sj_receipt_print_flg_name',
        'sj_receipt_print_user_id',
        'sj_receipt_print_user_name',
        'sj_receipt_print_date',
        'sj_receipt_print_time',
        'sj_qty',
        'sj_received_date',
        'sj_received_user',
        'lt',
        'due_date',
        'remarks'
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'sj_receipt_print_date' => 'date',
        'sj_receipt_print_time' => 'string', // Changed from 'time' to 'string'
        'sj_received_date' => 'date',
        'due_date' => 'date',
        'ctn_qty' => 'integer',
        'bulk_m3' => 'decimal:2',
        'wgt_kg' => 'decimal:2',
        'sj_receipt_print_flg' => 'boolean',
        'sj_qty' => 'integer',
        'lt' => 'integer',
    ];

    public function getCompositeKey()
    {
        return [
            'system_id' => $this->system_id,
            'site_cd' => $this->site_cd,
            'st_no' => $this->st_no,
        ];
    }
}
