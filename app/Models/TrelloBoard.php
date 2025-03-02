<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrelloBoard extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'trello_id'];

    public function lists()
    {
        return $this->hasMany(TrelloList::class, 'board_id');
    }
}
