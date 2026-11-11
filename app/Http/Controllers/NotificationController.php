<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(25);

        return view('notifications.index', compact('notifications'));
    }

    public function read(Notification $notification)
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->update(['is_read' => true, 'read_at' => now()]);

        return $notification->link ? redirect($notification->link) : back();
    }

    public function readAll()
    {
        NotificationService::markAllRead(auth()->id());
        flash_toast('success', 'All notifications marked as read.');
        return back();
    }
}
