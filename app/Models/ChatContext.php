<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatContext extends Model
{
    protected $fillable = ['session_id', 'context'];
}
