<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Notification $notification)
    {
        abort_unless($notification->user_id === Auth::id(), 403);

        $notification->update([
            'read_at' => now(),
        ]);

        return redirect($notification->url ?? route('notifications.index'));
    }

    public function readAll()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
