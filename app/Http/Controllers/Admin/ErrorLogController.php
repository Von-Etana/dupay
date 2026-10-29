<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ErrorLogController extends Controller
{
    public function index()
    {
        $data["page_title"] = "Error Logs";
        $data["page_sub_title"] = "Error Logs";
        $data["breadcrumb"] = [
            [
                "title" => "Home",
                "url" => route('admin.index')
            ],
            [
                "title" => $data["page_sub_title"],
                "url" => null
            ],
        ];
        
        $errors = ErrorLog::with('user')->latest()->get();
        return view('admin.error.index', compact('errors') + $data);
    }
}
