<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $admin = Auth::guard('admin')->user();
        $users = User::count();
        $hospices = User::where('role','hospice')->count();
        $nurses = User::where('role','nurse')->count();
        return view('admin.dashboard',['users' => $users, 'hospices' => $hospices, 'nurses' => $nurses]);
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/login');
    }
}
