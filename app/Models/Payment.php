<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'payment_gateway_id',
        'wallet_id',
        'type',
        'reference',
        'amount',
        'charge',
        'status_id'
    ];
    protected $appends = ['status'];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, "payment_gateway_id");
    }

    public function transaction(){
        return $this->hasOne(Transaction::class, "paymentReference", "reference");
    }

    public function Status(){
        return $this->belongsTo(Status::class, "status_id");
    }

    public function getStatusAttribute(){
        return  $this->Status()->name;
    }
}
