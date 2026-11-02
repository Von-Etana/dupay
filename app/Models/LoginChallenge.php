<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class LoginChallenge extends Model { public $incrementing=false; protected $keyType='string'; protected $guarded=[]; protected $casts=['expires_at'=>'datetime','sent_at'=>'datetime','consumed_at'=>'datetime']; protected static function booted(){ static::creating(function($m){$m->id=$m->id?:Str::uuid();}); } public function user(){return $this->belongsTo(User::class);} }
