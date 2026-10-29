<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EpinRate extends Model
{
    use HasFactory;
    
    protected $fillable = ['network_id', 'amount', 'rate'];

    public function network()
    {
        return $this->belongsTo(Telecom\Network::class);
    }
}
