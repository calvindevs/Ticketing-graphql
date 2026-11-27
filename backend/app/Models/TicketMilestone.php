<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketMilestone extends Model
{
    /** @use HasFactory<\Database\Factories\TicketMilestoneFactory> */
    use HasFactory;
    protected $fillable = [
        'ticket_id',
        'created_by',
        'title',
        'status',
        'due_date'
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
