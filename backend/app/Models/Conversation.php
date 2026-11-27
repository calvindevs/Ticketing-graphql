<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    /** @use HasFactory<\Database\Factories\ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'type'
    ];

    public function ticket(){
        return $this->belongsTo(Ticket::class);
    }

    public function messages(){
        return $this->hasMany(Message::class);
    }
}
