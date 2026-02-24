<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDeniedPermission extends Model
{
    protected $fillable = ['user_id', 'permission'];
}
