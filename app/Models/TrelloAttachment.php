<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrelloAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'url', 'trello_id', 'card_id'];

    public function card()
    {
        return $this->belongsTo(TrelloCard::class, 'card_id');
    }
}
