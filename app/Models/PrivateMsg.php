<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivateMsg extends Model
{
    protected $fillable = [
        'subject', 'message', 'from_user_id', 'to_user_id', 'is_read',
    ];
}
