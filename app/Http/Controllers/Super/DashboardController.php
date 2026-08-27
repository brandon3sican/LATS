<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\User;
use App\Models\LeaveApplication;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'offices' => Office::count(),
            'users'   => User::count(),
            'admins'  => User::whereHas('roles', function($q) {
                             $q->whereIn('key', ['office_admin', 'admin']);
                         })->count(),
            'leaves'  => [
                'total'       => LeaveApplication::count(),
                'approved'    => LeaveApplication::where('status', 'approved')->count(),
                'returned'    => LeaveApplication::where('status', 'returned')->count(),
                'disapproved' => LeaveApplication::where('status', 'disapproved')->count(),
            ],
        ];

        return view('super.dashboard', compact('stats'));
    }
}
