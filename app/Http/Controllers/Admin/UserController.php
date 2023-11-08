<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function hospices()
    {
        $hospices = User::where('role', 'hospice')->get();
        return view('admin.users.hospices',['hospices' => $hospices]);
    }

    public function nurses()
    {
        $nurses = User::where('role', 'nurse')->get();
        return view('admin.users.nurses',['nurses' => $nurses]);
    }

    public function blockUser(Request $request)
    {
        $id = $request->id;
        if ($request->user_type == 'block'){
            User::where('id',$id)->update([
                'is_blocked' => '1'
            ]);
        }else{
            User::where('id',$id)->update([
                'is_blocked' => '0'
            ]);
        }
    }

    public function approveUser(Request $request)
    {
        $id = $request->id;
        if ($request->user_type == 'approve'){
            User::where('id',$id)->update([
                'is_approved' => '1'
            ]);
        }else{
            User::where('id',$id)->update([
                'is_approved' => '0'
            ]);
        }
    }
}
