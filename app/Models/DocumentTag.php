<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DocumentTag extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(CustomerDocument::class);
    }

    public static function getTypes(): array
    {
        return [
            'business_area' => 'Business Area',
            'business_type' => 'Business Type',
        ];
    }    //

    public function sales_opportunities(): BelongsToMany
    {
        return $this->belongsToMany(SalesOpportunity::class);
    }
}
