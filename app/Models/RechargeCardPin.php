<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class RechargeCardPin extends Model
{
    protected $fillable = ['network','denomination','pin','serial_number','batch_number','supplier','expires_at','status','added_by'];
    protected $casts = ['denomination' => 'decimal:2', 'expires_at' => 'date'];
    public function setPinAttribute($value): void { $this->attributes['pin'] = Crypt::encryptString($value); }
    public function getPinAttribute($value): string { try { return Crypt::decryptString($value); } catch (\Throwable $e) { return ''; } }
    public function getMaskedPinAttribute(): string { $pin = $this->pin; return $pin ? str_repeat('•', max(0, strlen($pin)-4)).substr($pin, -4) : '—'; }
}
