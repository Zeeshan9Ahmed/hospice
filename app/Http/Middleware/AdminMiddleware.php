<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // if(auth()->check() && auth()->user()->role === "admin"){
        //     return $next($request);
        // }
        // return  redirect()->route('login');

        if(Auth::guard('admin')->check()){
        return $next($request);
      }
        return redirect('login')->with('error','You dont have admin access');
    }
}
