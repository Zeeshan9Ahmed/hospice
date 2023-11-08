<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    public function __construct()
    {
        $this->middleware('guest:admin')->only('login');
    }

    public function login(){

        return view('admin.login');

    }

    public function login_process(Request $request)
    {
        $controls=$request->all();
        $rules=array(
            'email'=>"required|exists:admins,email",
            "password"=>"required");
        $validator=Validator::make($controls,$rules);
        if ($validator->fails()) {
            // dd($validator);
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $admin = Admin::where('email', $request->email)->first();

        if (Hash::check($request->password, $admin->password)) {
            Auth::guard('admin')->login($admin);

            return redirect()->route('dashboard');
        }

        return redirect()->back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => 'Incorrect email address or password']);
    }
}
