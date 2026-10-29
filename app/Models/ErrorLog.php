<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErrorLog extends Model
{
    use HasFactory;
    protected $fillable = [
        "user_id",
        "user_name",
        "message",
        "model",
        "data",
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
