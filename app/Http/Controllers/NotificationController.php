<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAllAsRead()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Laravel automatically updates the 'read_at' column to the current timestamp for all unread notifications!
        $user->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }
}
