<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserExtract extends Model
{
  
    protected $casts = [
        'created_at' => 'datetime:d/m/Y H:i',
    ];
  
    protected $table = 'user_extract';

}
