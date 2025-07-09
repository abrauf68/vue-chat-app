<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessageReaction extends Model
{
    use HasFactory;

    protected $fillable = ['group_message_id', 'user_id', 'emoji'];

    public function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function message()
    {
        return $this->belongsTo(GroupMessage::class,'group_message_id');
    }
}
