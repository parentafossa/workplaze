<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrelloCardActivity extends Model
{
    use HasFactory;

    protected $fillable = ['card_id', 'action', 'list_from', 'list_to', 'status', 'user','created_at'];

    public function card()
    {
        return $this->belongsTo(TrelloCard::class, 'card_id');
    }
}
