<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrelloComment extends Model
{
    use HasFactory;
    protected $fillable = ['text', 'emp_id', 'trello_id', 'card_id'];

    public function card()
    {
        return $this->belongsTo(TrelloCard::class, 'card_id');
    }


    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }
}
