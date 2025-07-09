<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'group_id',
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

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function repliedTo()
    {
        return $this->belongsTo(GroupMessage::class, 'replied_to');
    }

    public function groupMessageDeletion()
    {
        return $this->hasMany(GroupMessageDeletion::class, 'group_message_id');
    }

    public function groupMessageReaction()
    {
        return $this->hasMany(GroupMessageReaction::class, 'group_message_id');
    }
}
