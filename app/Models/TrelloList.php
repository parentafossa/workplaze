<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrelloList extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'trello_id', 'board_id'];

    public function board()
    {
        return $this->belongsTo(TrelloBoard::class, 'board_id');
    }

    public function cards()
    {
        return $this->hasMany(TrelloCard::class, 'list_id');
    }
}
