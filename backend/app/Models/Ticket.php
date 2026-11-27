<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'user_id',
        'admin_id',
        'agent_id',
        'title',
        'description',
        'priority',
        'status',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime'
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(){
        return $this->belongsTo(User::class, 'admin_id');
    }
    
    public function agent(){
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function milestones(){
        return $this->hasMany(TicketMilestone::class);
    }

    public function conversations(){
        return $this->hasMany(Conversation::class);
    }
}
