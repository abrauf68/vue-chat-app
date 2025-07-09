<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessageDeletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_message_id',
        'user_id',
    ];

    public function groupMessage()
    {
        return $this->belongsTo(GroupMessage::class, 'message_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
