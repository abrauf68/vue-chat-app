<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'text',
        'attachment',
        'attachment_size',
        'replied_to',
        'is_forwarded',
        'is_deleted'
    ];


    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    
    public function repliedTo()
    {
        return $this->belongsTo(Message::class, 'replied_to');
    }

    public function messageDeletion()
    {
        return $this->hasMany(MessageDeletion::class, 'message_id');
    }

    public function messageReaction()
    {
        return $this->hasMany(MessageReaction::class, 'message_id');
    }
}
