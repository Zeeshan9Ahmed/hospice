<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(){
        $feedbacks = Feedback::with('user:id,first_name,last_name')->with('case:id,patient_name,location,dob,phone_number,gender')->get();
        return view('admin.feedbacks.index',['feedbacks'=>$feedbacks]);
    }
}
