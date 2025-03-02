<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrelloCard extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'trello_id', 'list_id','business_area','urgent'];

    public function list()
    {
        return $this->belongsTo(TrelloList::class, 'list_id');
    }

    public function comments()
    {
        return $this->hasMany(TrelloComment::class, 'card_id');
    }
    public function activities()
    {
        return $this->hasMany(TrelloCardActivity::class, 'card_id');
    }
    public function attachments()
    {
        return $this->hasMany(TrelloAttachment::class, 'card_id');
    }
    public function empbasic()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }
}
