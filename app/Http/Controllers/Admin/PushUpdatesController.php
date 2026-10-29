<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\PushUpdate;
use App\Models\User;

class PushUpdatesController extends Controller
{
    public function index()
    {
        $data["page_title"] = "Notification";
        $data["page_sub_title"] = "Push Updates";
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
        
     
        $updates = PushUpdate::latest('id')->paginate(20);

        return view('admin.push.index', compact('updates'), $data);
    }
    
    public function create()
    {
        $data["page_title"] = "Notification";
        $data["page_sub_title"] = "Push Updates";
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
        
        return view('admin.push.create', $data);
    }
    
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'status'  => 'required|in:0,1'
        ]);

        $pushUpdate = PushUpdate::create([
            'title' => $request->title,
            'message' => $request->message,
            'status' => $request->status == '1' ? 'save and send' : 'save',
            'sent' => false,
        ]);

        if ($request->status == 1) {
            $tokens = User::whereNotNull('fcmToken')->pluck('fcmToken')->toArray();

            if (!empty($tokens)) {
                sendFcmToMultipleDevices(
                    $tokens,
                    $request->title,
                    $request->message,
                    ['type' => 'announcement']
                );
            }

            $pushUpdate->update([
                'sent' => true,
                'sent_at' => now(),
            ]);
        }
        
        session()->flash('message', 'Push update saved' . ($request->status == 1 ? ' and sent.' : '.'));
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return back();
        
    }
    
    public function edit(PushUpdate $update)
    {
        $data["page_title"] = "Notification";
        $data["page_sub_title"] = "Push Updates";
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
        
        return view('admin.push.edit', compact('update') + $data);
    }
    
    public function save(Request $request, PushUpdate $update)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'status'  => 'required|string'
        ]);

        $update->update([
            'title' => $request->title,
            'message' => $request->message,
            'status' => $request->status,
        ]);
        
        session()->flash('message', 'Push update saved');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return back();
        
    }
    
    public function resend(Request $request, PushUpdate $update)
    {
        $tokens = User::whereNotNull('fcmToken')->pluck('fcmToken')->toArray();

        if (!empty($tokens)) {
            sendFcmToMultipleDevices(
                $tokens,
                $update->title,
                $update->message,
                ['type' => 'announcement']
            );
        }

        $update->update([
            'sent' => true,
            'sent_at' => now(),
        ]);
        
        session()->flash('message', 'Push update sent successfully');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return back();
        
    }
    
    public function destroy(PushUpdate $update)
    {
        $update->delete();

        session()->flash('message', 'Push update deleted successfully.');
        Session::flash('type', 'success');
        Session::flash('title', 'Success');

        return back();
    }
}
