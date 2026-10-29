<?php

use Illuminate\Support\Facades\DB;

if (!function_exists('getSetting')) {
    function getSetting($key, $default = null)
    {
        $setting = DB::table('settings')->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
