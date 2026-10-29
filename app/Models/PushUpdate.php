<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushUpdate extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'message',
        'status',
        'sent',
        'sent_at'];
}
