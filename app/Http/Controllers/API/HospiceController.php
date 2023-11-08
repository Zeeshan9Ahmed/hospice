<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponser;
use App\Models\CaseApproval;
use App\Models\CaseRequest;
use App\Models\CaseShift;
use App\Models\HospiceCase;
use App\Models\Notification;
use App\Models\NurseAvailability;
use App\Models\ShiftRequest;
use App\Models\Shifts;
use App\Models\Shits;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class HospiceController extends Controller
{
    use ApiResponser;

    /** Case List */

    public function caseList(Request $request)
    {

        $id = Auth::User()->id;

        $status =  $request->status;

        $discipline = User::where('id', auth()->user()->id)->first();


        $caset =  CaseApproval::whereNurseId($id)->whereIn('status', ['unapproved', 'approved'])->pluck('case_id')->first();
        // return $caset;
        $disciplineArr = explode(',', $discipline->discipline);

        if ($status == 'available') {
            $all_cases = HospiceCase::with(['business_name' => function ($query) {
                $query->select('id', 'business_name as name');
            }])->with('nurse_details:id,role,first_name,last_name,email,rates,card_id,is_card')
                // ->whereIn('id',function($query){
                //     $query->select('case_id')
                //     ->from(with(new CaseShift)->getTable())
                //     ->where('status', 'available');
                // })
                ->where('status', $status)->where('nurse_id', null)->where(function ($cases) use ($disciplineArr) {
                    foreach ($disciplineArr as $arr) {
                        $cases->orWhere('discipline_needed', 'like', "%$arr%");
                    }
                })->orderBy('id', 'DESC')
                ->get();

            $cases = [];

            if ($all_cases->count() > 0) {

                foreach ($all_cases as $c) {

                    $check = CaseShift::where('case_id', $c->id)->where('status', 'available')->get();

                    if ($c->status == 'available' && count($check) > 0) {

                        $cases[] = $c;
                    } elseif ($c->care_level != 'continuous care' && $c->status == 'available') {

                        $cases[] = $c;
                    }
                }

                foreach ($cases as $case) {

                    $shifts = CaseShift::with('nurse_details')->where('case_id', $case->id)
                        ->orderBy('date')
                        ->get()
                        ->groupBy('date')->map(function ($value, $key) {
                            return [
                                'date' => $key,
                                'shifts' => $value
                            ];
                        })->values();

                    foreach ($shifts as $shift) {

                        foreach ($shift['shifts'] as $s) {

                            if ($s->shift == 1) {

                                $s['open_shift'] = '12:00 PM';
                                $s['close_shift'] = '12:00 AM';
                            } elseif ($s->shift == 2) {

                                $s['open_shift'] = '12:00 AM';
                                $s['close_shift'] = '12:00 PM';
                            }
                        }
                    }

                    $case['shifts'] = $shifts;
                }
            }
        } else {

            $all_cases = HospiceCase::with(['business_name' => function ($query) {
                $query->select('id', 'business_name as name');
            }])
                // ->with('nurse_details:id,role,first_name,last_name,email,rates,card_id,is_card')
                ->where(function ($all_cases) use ($disciplineArr) {
                    foreach ($disciplineArr as $arr) {
                        $all_cases->orWhere('discipline_needed', 'like', "%$arr%");
                    }
                })->orderBy('id', 'DESC')
                ->get();

            $cases = [];

            if ($all_cases->count() > 0) {

                foreach ($all_cases as $c) {

                    if ($c->status == $status && $c->nurse_id == $id) {

                        $cases[] = $c;
                    } else {

                        $shift_status = CaseShift::where('case_id', $c->id)->where('nurse_id', $id)->where('status', $status)->first();

                        if ($shift_status) {
                            $cases[] = $c;
                        }
                    }
                }

                foreach ($cases as $case) {

                    //calculate remaining time
                    $start = $case->start_date . " " . $case->open_shift;
                    $end = $case->end_date . " " . $case->close_shift;

                    $d1 = strtotime($start);
                    $d2 = strtotime($end);

                    $datediff = $d2 - $d1;
                    $days = round($datediff / (60 * 60 * 24));
                    // $hours = abs($d1 - $d2)/3600;
                    $hours = round($datediff / (60 * 60));
                    $minutes = round($datediff / (60));
                    $seconds = round($datediff);


                    $case["working_hour"] = $hours . " hr, " . $minutes . " min, " . $seconds . " sec.";

                    if ($status == 'cancelled') {

                        $case["nurse_details"] = null;
                    } else {

                        $case["nurse_details"] = User::where('id', $case->nurse_id)->first();
                    }



                    $shifts = CaseShift::with('nurse_details')->where('case_id', $case->id)
                        ->orderBy('date')
                        ->get()
                        ->groupBy('date')->map(function ($value, $key) {
                            return [
                                'date' => $key,
                                'shifts' => $value
                            ];
                        })->values();

                    foreach ($shifts as $shift) {

                        foreach ($shift['shifts'] as $s) {

                            if ($s->shift == 1) {

                                // if($status == 'inprocess'){

                                //     $s["nurse_details"] = User::where('id', $case->nurse_id)->first();

                                // }else{

                                //     $s["nurse_details"] = null;

                                // }

                                $s['open_shift'] = '12:00 PM';
                                $s['close_shift'] = '12:00 AM';
                            } elseif ($s->shift == 2) {

                                $s['open_shift'] = '12:00 AM';
                                $s['close_shift'] = '12:00 PM';
                            }
                        }
                    }

                    $case['shifts'] = $shifts;
                }
            }
        }

        $data = [
            'case_id' => $caset ?? 0,
            "cases" => $cases
        ];

        return response()->json([
            "status" => 1,
            "message" => "cases!",
            "data" => $data
        ]);
    }

    /** Create Cases */

    public function createCase(Request $request)
    {

        $data = $request->all();
        $rules = [
            "patient_name" => "required",
            "location" => "required",
            "dob" => "required",
            "phone_number" => "required",
            "gender" => "required",
            "discipline_needed" => "required",
            "care_level" => "required",
            "start_date" => "required",
            "end_date" => "nullable",
            "case_status" => "required",
            "open_shift" => "nullable",
            "close_shift" => "nullable",
            "note" => "nullable"
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $case = new HospiceCase();
        $case->user_id = Auth::user()->id;
        $case->patient_name = $request->patient_name;
        $case->location = $request->location;
        $case->dob = $request->dob;
        $case->phone_number = $request->phone_number;
        $case->gender = $request->gender;
        $case->discipline_needed = $request->discipline_needed;
        $case->care_level = $request->care_level;
        $case->start_date = Carbon::createFromFormat('d/m/Y', $request->start_date)->format('m/d/Y');
        $case->end_date = Carbon::createFromFormat('d/m/Y', $request->end_date)->format('m/d/Y');
        $case->case_status = $request->case_status;
        $case->open_shift = $request->open_shift;
        $case->close_shift = $request->close_shift;
        $case->note = $request->note;
        $case->save();

        if ($request->care_level == 'continuous care') {

            $start_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->start_date)->format('d-m-Y'));
            $end_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->end_date)->format('d-m-Y'));
            $days = $end_date->diffInDays($start_date);

            $shift_one = new CaseShift();
            $shift_one->case_id = $case->id;
            $shift_one->date = $start_date->format('m/d/Y');
            $shift_one->time = '12AM to 12PM';
            $shift_one->shift = 1;
            $shift_one->status = 'available';
            $shift_one->save();

            $shift_two = new CaseShift();
            $shift_two->case_id = $case->id;
            $shift_two->date = $start_date->format('m/d/Y');
            $shift_two->time = '12PM to 12AM';
            $shift_two->shift = 2;
            $shift_two->status = 'available';
            $shift_two->save();

            for ($i = 1; $i <= $days; $i++) {

                $date = $start_date->addDays(1)->format('m/d/Y');

                $shift_one = new CaseShift();
                $shift_one->case_id = $case->id;
                $shift_one->date = $date;
                $shift_one->time = '12AM to 12PM';
                $shift_one->shift = 1;
                $shift_one->status = 'available';
                $shift_one->save();

                $shift_two = new CaseShift();
                $shift_two->case_id = $case->id;
                $shift_two->date = $date;
                $shift_two->time = '12PM to 12AM';
                $shift_two->shift = 2;
                $shift_two->status = 'available';
                $shift_two->save();
            }

            $hospice_case = HospiceCase::select(
                'id',
                'user_id',
                'patient_name',
                'location',
                'dob',
                'phone_number',
                'gender',
                'discipline_needed',
                'care_level',
                'start_date',
                'end_date',
                'case_status',
                'open_shift',
                'close_shift',
                'note',
                'status'
            )->where('id', $case->id)->first();

            $shifts = CaseShift::with('nurse_details')->where('case_id', $case->id)
                ->orderBy('date')
                ->get()
                ->groupBy('date')->map(function ($value, $key) {
                    return [
                        'date' => $key,
                        'shifts' => $value
                    ];
                })->values();

            $hospice_case['shifts'] = $shifts;


            if ($hospice_case) {
                return response()->json([
                    "status" => 1,
                    "message" => "Case created successfully!",
                    'data' => [
                        'case' => $hospice_case
                    ]
                ]);
            }
            return response()->json([
                "status" => 0,
                "message" => "Failed to create case!",
            ]);
        }

        if ($case) {
            return response()->json([
                "status" => 1,
                "message" => "Case created successfully!",
                'data' => [
                    'case' => $case
                ]
            ]);
        }
        return response()->json([
            "status" => 0,
            "message" => "Failed to create case!",
        ]);
    }

    /** Assign Nurse */

    public function assignNurse(Request $request)
    {
        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "id" => "required",
            "case_id" => "required|exists:hospice_cases,id",
            "start_date" => "nullable",
            "end_date" => "nullable",
        ];

        $customMessages = [
            'required' => 'The :attribute field is required.',
            'numeric' => 'The :attribute  Must be Numeric',
            'exists' => 'Select a case first',
        ];

        $validator = Validator::make($data, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $nurse = NurseAvailability::find($request->id)->where('status', 'available');

        if ($nurse) {

            $case = HospiceCase::where('id', $request->case_id)->first();

            if ($case->status == 'completed') {

                if ($case->care_level == 'continuous care') {

                    $case = HospiceCase::where('id', $request->case_id)->first();

                    $new_case = $case->replicate();
                    $new_case->status = 'available';
                    $new_case->nurse_id = null;
                    $new_case->start_date = $request->start_date;
                    $new_case->end_date = $request->end_date;
                    $new_case->is_sheet_filled = 0;
                    $new_case->is_platform_fee_paid = 0;
                    $new_case->save();

                    $start_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->start_date)->format('d-m-Y'));
                    $end_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $request->end_date)->format('d-m-Y'));
                    $days = $end_date->diffInDays($start_date);

                    $shift_one = new CaseShift();
                    $shift_one->case_id = $new_case->id;
                    $shift_one->date = $start_date->format('d/m/Y');
                    $shift_one->time = '12AM to 12PM';
                    $shift_one->shift = 1;
                    $shift_one->status = 'available';
                    $shift_one->save();

                    $shift_two = new CaseShift();
                    $shift_two->case_id = $new_case->id;
                    $shift_two->date = $start_date->format('d/m/Y');
                    $shift_two->time = '12AM to 12PM';
                    $shift_two->shift = 2;
                    $shift_two->status = 'available';
                    $shift_two->save();

                    for ($i = 1; $i <= $days; $i++) {

                        $date = $start_date->addDays(1)->format('d/m/Y');

                        $shift_one = new CaseShift();
                        $shift_one->case_id = $new_case->id;
                        $shift_one->date = $date;
                        $shift_one->time = '12AM to 12PM';
                        $shift_one->shift = 1;
                        $shift_one->status = 'available';
                        $shift_one->save();

                        $shift_two = new CaseShift();
                        $shift_two->case_id = $new_case->id;
                        $shift_two->date = $date;
                        $shift_two->time = '12PM to 12AM';
                        $shift_two->shift = 2;
                        $shift_two->status = 'available';
                        $shift_two->save();
                    }

                    $hospice_case = HospiceCase::select(
                        'id',
                        'user_id',
                        'patient_name',
                        'location',
                        'dob',
                        'phone_number',
                        'gender',
                        'discipline_needed',
                        'care_level',
                        'start_date',
                        'end_date',
                        'case_status',
                        'open_shift',
                        'close_shift',
                        'note',
                        'status'
                    )->where('id', $new_case->id)->with('shifts:id,case_id,date,time,shift,status')->get();

                    return response()->json([
                        "status" => 0,
                        "message" => "Case replicated with shifts! Could not be assigned to one nurse, assign shifts individually!",
                        'data' => [
                            'case' => $hospice_case
                        ]
                    ]);
                }

                $nurse_id = NurseAvailability::where('id', $request->id)->pluck('user_id')->first();

                $nurse_discipline = User::where('id', $nurse_id)->pluck('discipline')->first();

                $case_discipline = HospiceCase::where('id', $request->case_id)->where('discipline_needed', 'like', "%$nurse_discipline%")->first();

                if ($case_discipline) {

                    //$replicated = HospiceCase::where('patient_name', $case->patient_name)->where('start_date', $request->start_date)->where('status', 'pending')->first();

                    $new_case = '';

                    //if ($replicated == null){

                    $currentDate = Carbon::now();

                    $new_case = $case->replicate();
                    $new_case->status = 'pending';
                    $new_case->nurse_id = $nurse_id;
                    $new_case->start_date = $request->start_date;
                    $new_case->end_date = $request->start_date;
                    $new_case->is_sheet_filled = 0;
                    $new_case->is_platform_fee_paid = 0;
                    $new_case->save();

                    $case_request = CaseRequest::where('nurse_id', $id)->where('case_id', $new_case->id)->first();

                    // }else{

                    //     $case_request = CaseRequest::where('nurse_id', $id)->where('case_id', $replicated->id)->first();

                    // }

                    if ($case_request) {
                        return response()->json([
                            "status" => 1,
                            "message" => "Case request has already been sent!",
                        ]);
                    } else {

                        $send_request = new CaseRequest();
                        $send_request->nurse_id = $nurse_id;
                        $send_request->hospice_id = $id;
                        $send_request->availability_id = $request->id;
                        $send_request->case_id = $new_case->id;
                        $send_request->status = 'pending';
                        $save = $send_request->save();

                        $data = [
                            'request' => $send_request
                        ];

                        if ($save) {

                            $user = Auth::user();

                            $post_id = $new_case->id;
                            $shift_id = null;
                            $request_id = $send_request->id;

                            $sender_id = $id;
                            $receiver_id = $nurse_id;
                            $type = "Case Assigned";
                            $message = $user->business_name . " assigned you a case.";
                            // $notify = new CoreController;
                            $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

                            return response()->json([
                                "status" => 1,
                                "message" => "Case request has been sent to the nurse!",
                                'data' => $data
                            ]);
                        }
                    }
                } else {

                    return response()->json([
                        "status" => 1,
                        "message" => "Nurse's discipline doesn't match with your case!",
                    ]);
                }
            } else {
                return response()->json([
                    "status" => 1,
                    "message" => "This case could not be assigned!",
                ]);
            }
        } else {
            return response()->json([
                "status" => 0,
                "message" => 'Nurse does not exist',
            ]);
        }

        return response()->json([
            "status" => 0,
            "message" => "Failed to assign nurse!",
        ]);
    }

    /** View Posts */

    public function viewPosts()
    {

        $user_id =  auth()->user()->id;

        $cases = HospiceCase::with('nurse_details')->where('user_id', $user_id)->get();

        foreach ($cases as $case) {

            $shifts = CaseShift::with('nurse_details')->where('case_id', $case->id)
                ->orderBy('date')
                ->get()
                ->groupBy('date')->map(function ($value, $key) {
                    return [
                        'date' => $key,
                        'shifts' => $value
                    ];
                })->values();


            $case['shifts'] = $shifts;
        }

        $data = [
            "posts" => $cases,
        ];

        return response()->json([
            "status" => 1,
            "message" => "posts!",
            "data" => $data
        ]);
    }

    /** Edit Post */

    public function editPost(Request $request)
    {
        $controls = $request->all();

        $rules = array(
            "post_id" => "required|exists:hospice_cases,id",
            "patient_name" => "sometimes|required",
            "location" => "sometimes|required",
            "dob" => "sometimes|required",
            "phone_number" => "sometimes|required",
            "gender" => "sometimes|required",
            "discipline_needed" => "sometimes|required",
            "care_level" => "sometimes|required",
            "start_date" => "sometimes|required",
            "end_date" => "sometimes|required",
            "open_shift" => "sometimes|required",
            "close_shift" => "sometimes|required",
            "note" => "sometimes|required"
        );
        $customMessages = [
            'required' => 'The :attribute  is required.',
            'exists' => 'The :attribute does not exist',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json([
                'status' => 0,
                'message' => $validator->errors()->all()[0]
            ], 400);
        }

        $post = HospiceCase::find($request->post_id);

        if ($post->status == 'inprocess' || $post->status == 'completed') {
            return response()->json([
                'status' => 0,
                'message' => 'You can not edit this post'
            ]);
        } else {

            $post->user_id = Auth::user()->id;
            $post->patient_name = $request->patient_name;
            $post->location = $request->location;
            $post->dob = $request->dob;
            $post->phone_number = $request->phone_number;
            $post->gender = $request->gender;
            $post->discipline_needed = $request->discipline_needed;
            $post->care_level = $request->care_level;
            if ($request->start_date) {
                $post->start_date = $request->start_date;
            }
            if ($request->end_date) {
                $post->end_date = $request->end_date;
            }

            $post->open_shift = $request->open_shift;
            $post->close_shift = $request->close_shift;
            $post->note = $request->note;

            $save = $post->save();

            if ($post->care_level == 'continuous care') {

                if ($request->start_date || $request->start_date) {

                    $old_shifts = CaseShift::where('case_id', $post->id)->get();

                    foreach ($old_shifts as $shift) {
                        $shift->delete();
                    }

                    $start_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $post->start_date)->format('d-m-Y'));
                    $end_date = Carbon::parse(Carbon::createFromFormat('d/m/Y', $post->end_date)->format('d-m-Y'));
                    $days = $end_date->diffInDays($start_date);

                    $shift_one = new CaseShift();
                    $shift_one->case_id = $post->id;
                    $shift_one->date = $start_date->format('d/m/Y');
                    $shift_one->time = '12 hrs';
                    $shift_one->shift = 1;
                    $shift_one->status = 'available';
                    $shift_one->save();

                    $shift_two = new CaseShift();
                    $shift_two->case_id = $post->id;
                    $shift_two->date = $start_date->format('d/m/Y');
                    $shift_two->time = '12 hrs';
                    $shift_two->shift = 2;
                    $shift_two->status = 'available';
                    $shift_two->save();

                    for ($i = 1; $i <= $days; $i++) {

                        $date = $start_date->addDays(1)->format('d/m/Y');

                        $shift_one = new CaseShift();
                        $shift_one->case_id = $post->id;
                        $shift_one->date = $date;
                        $shift_one->time = '12 hrs';
                        $shift_one->shift = 1;
                        $shift_one->status = 'available';
                        $shift_one->save();

                        $shift_two = new CaseShift();
                        $shift_two->case_id = $post->id;
                        $shift_two->date = $date;
                        $shift_two->time = '12 hrs';
                        $shift_two->shift = 2;
                        $shift_two->status = 'available';
                        $shift_two->save();
                    }
                }
            }

            $hospice_case = HospiceCase::select(
                'id',
                'user_id',
                'patient_name',
                'location',
                'dob',
                'phone_number',
                'gender',
                'discipline_needed',
                'care_level',
                'start_date',
                'end_date',
                'case_status',
                'open_shift',
                'close_shift',
                'note',
                'status'
            )->where('id', $post->id)->with('shifts:id,case_id,date,time,shift,status')->get();

            if ($hospice_case) {
                return response()->json([
                    "status" => 1,
                    "message" => "Post updated successfully!",
                    'data' => [
                        'case' => $hospice_case
                    ]
                ]);
            }
            return response()->json([
                "status" => 0,
                "message" => "Failed to update post!",
            ]);
        }
    }

    /** Delete Post */

    public function deletePost(Request $request)
    {
        $controls = $request->all();

        $rules = array(
            "post_id" => "required|exists:hospice_cases,id",
        );
        $customMessages = [
            'required' => 'The :attribute  is required.',
            'exists' => 'The :attribute does not exist',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }


        $post = HospiceCase::where('id', $request->post_id)->first();
        if ($post->status == 'inprocess' || $post->status == 'completed') {

            return $this->error('You can not delete this post.', 400);
        } else {

            $post->delete();

            $shifts = CaseShift::where('case_id', $request->post_id)->get();

            foreach ($shifts as $shift) {
                $shift->delete();
            }

            return $this->success('Post deleted successfully.');
        }
    }


    /** Add Notification */

    public function add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message)
    {

        $check = User::where('id', $receiver_id)->first();
        if ($check) {
            $notification = new Notification;
            $notification->sender_id = $sender_id;
            $notification->receiver_id = $receiver_id;
            $notification->post_id = $post_id;
            $notification->shift_id = $shift_id;
            $notification->request_id = $request_id;
            $notification->type = $type;
            $notification->message = $message;
            $notification->save();

            //Push Notification
            $firebaseToken = User::where('id', $receiver_id)->first();
            $SERVER_API_KEY = 'AAAAQn8vSX4:APA91bEIF1ciu-36QQxIrolcjUxmD4KLzhFQSptposJ8xBEUgbZbJm9HqasQx1jLea_7UTClkgGIFIxGT4DJ0xiX4g7epg7_mV_hRaG3rQWlMIO3LxhuydRpmQguPxe7LIjUwxuXEkFr';

            $data = [
                "to" => $firebaseToken->device_token,
                "data" => [
                    "title" => 'Hospice',
                    "type" => $type,
                    "body" => $message,
                    "case_id" => $post_id,
                ],
                "notification" => [
                    "title" => 'Hospice',
                    "type" => $type,
                    "body" => $message,
                    "case_id" => $post_id,
                ]
            ];
            $dataString = json_encode($data);
            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            $response = curl_exec($ch);
        }
    }

    /** Assign Shift To Nurse */

    public function assignShiftToNurse(Request $request)
    {

        $id = Auth::user()->id;
        $data = $request->all();

        $rules = [
            "id" => "required",
            "case_id" => "required|exists:hospice_cases,id",
            "shift_ids" => "required",
        ];

        $customMessages = [
            'required' => 'The :attribute field is required.',
            'numeric' => 'The :attribute  Must be Numeric',
            'exists' => 'Select a case first',
        ];

        $validator = Validator::make($data, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $nurse = NurseAvailability::find($request->id)->where('status', 'available');
        if ($nurse) {

            $case = HospiceCase::where('id', $request->case_id)->first();

            if ($case->care_level == 'continuous care') {

                $nurse_id = NurseAvailability::where('id', $request->id)->pluck('user_id')->first();

                $nurse_discipline = User::where('id', $nurse_id)->pluck('discipline')->first();

                $case_discipline = HospiceCase::where('id', $request->case_id)->where('discipline_needed', 'like', "%$nurse_discipline%")->first();

                if ($case_discipline) {

                    $data = [];

                    foreach ($request->shift_ids as $shift_id) {

                        $shift_exist = CaseShift::where('id', $shift_id)->first();

                        if ($shift_exist) {

                            $send_request = new ShiftRequest();
                            $send_request->nurse_id = $nurse_id;
                            $send_request->availability_id = $request->id;
                            $send_request->hospice_id = $id;
                            $send_request->case_id = $request->case_id;
                            $send_request->shift_id = $shift_id;
                            $send_request->status = 'pending';
                            $save = $send_request->save();

                            $data[] = [
                                'request' => $send_request
                            ];
                        } else {

                            return response()->json([
                                "status" => 0,
                                "message" => 'Shift Id ' . $shift_id . ' does not exist',
                            ]);
                        }

                        if ($save) {

                            $user = Auth::user();

                            $sender_id = $id;
                            $receiver_id = $nurse_id;
                            $post_id = $request->case_id;
                            $shift_id = $shift_id;
                            $request_id = $send_request->id;
                            $type = "Shift Assigned";
                            $message = $user->business_name . " assigned you a shift.";
                            // $notify = new CoreController;
                            $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

                            return response()->json([
                                "status" => 1,
                                "message" => "Shift request has been sent to the nurse!",
                                'data' => $data
                            ]);
                        }
                    }
                } else {

                    return response()->json([
                        "status" => 1,
                        "message" => "Nurse's discipline doesn't match with your case!",
                    ]);
                }
            } else {
                return response()->json([
                    "status" => 0,
                    "message" => "This case does not have shifts!",
                ]);
            }
        } else {
            return response()->json([
                "status" => 0,
                "message" => 'Nurse does not exist',
            ]);
        }

        return response()->json([
            "status" => 0,
            "message" => "Failed to assign nurse!",
        ]);
    }

    /** Approve Reject Case Request */

    public function approveRejectCaseRequest(Request $request)
    {

        $id = Auth::user()->id;

        $name = Auth::user()->business_name;

        $data = $request->all();

        $rules = [
            "request_id" => "required",
            "status" => "required",
            "notification_id" => "nullable"
        ];

        $customMessages = [
            'required' => 'The :attribute field is required.',
        ];

        $validator = Validator::make($data, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $delete_notification = Notification::where('id', $request->notification_id)->delete();

        $type = '';
        $message = '';

        if ($request->status == 'approved') {

            $case_request = CaseApproval::where('id', $request->request_id)->first();

            $approved_nurse_requests = CaseApproval::where('nurse_id', $case_request->nurse_id)->where('status', 'approved')->get();

            $check_case = '';

            if ($approved_nurse_requests->count() > 0) {

                foreach ($approved_nurse_requests as $approved_nurse_request) {

                    $check_case = HospiceCase::where('id', $approved_nurse_request->case_id)->first();

                    if ($check_case->status == 'completed' || $check_case->status == 'cancelled') {

                        $already_assigned = CaseApproval::where('case_id', $case_request->case_id)->where('status', 'approved')->first();

                        if ($already_assigned) {

                            // return response()->json([
                            //     "status" => 0,
                            //     "message" => "A nurse has already been approved for this case",
                            // ]);

                        } else {

                            $case_request->status = 'approved';
                            $case_request->save();


                            $shift_id = $case_request->shift_id;
                            CaseShift::where('id', $shift_id)->update(['status' => 'inprocess']);

                            if ($case_request->shift_id == NULL) {

                                $assign = HospiceCase::where('id', $case_request->case_id)->first();
                                $assign->nurse_id = $case_request->nurse_id;
                                $assign->status = 'inprocess';
                                $assign->save();
                            } else {

                                $shift_status = CaseShift::where('id', $case_request->shift_id)->first();
                                $shift_status->status = 'inprocess';
                                $shift_status->nurse_id = $case_request->nurse_id;
                                $shift_status->save();
                            }

                            $type = "Case Accepted";
                            $message = $name . " has approved your case request.";
                        }
                    } elseif ($check_case->status == 'available') {

                        if ($case_request->case_id == $approved_nurse_request->case_id) {

                            $case_request->status = 'approved';
                            $case_request->save();

                            $shift_status = CaseShift::where('id', $case_request->shift_id)->first();
                            $shift_status->status = 'inprocess';
                            $shift_status->nurse_id = $case_request->nurse_id;
                            $shift_status->save();

                            $type = "Case Accepted";
                            $message = $name . " has approved your case request.";
                        } else {

                            return response()->json([
                                "status" => 0,
                                "message" => "This nurse already has a case in process",
                            ]);
                        }
                    }
                }
            } else {

                $case_request->status = 'approved';
                $case_request->save();

                if ($case_request->shift_id == NULL) {

                    $assign = HospiceCase::where('id', $case_request->case_id)->first();
                    $assign->nurse_id = $case_request->nurse_id;
                    $assign->status = 'inprocess';
                    $assign->save();
                } else {

                    $shift_status = CaseShift::where('id', $case_request->shift_id)->first();
                    $shift_status->status = 'inprocess';
                    $shift_status->nurse_id = $case_request->nurse_id;
                    $shift_status->save();
                }

                $type = "Case Accepted";
                $message = $name . " has approved your case request.";
            }
        } elseif ($request->status == 'rejected') {

            $case_request = CaseApproval::where('id', $request->request_id)->first();

            $case_request->status = 'rejected';
            $case_request->save();

            $type = "Case Rejected";
            $message = $name . " has rejected your case request.";
        }


        $post_id = $case_request->case_id;
        $shift_id = $case_request->shift_id == NULL ? NULL : $case_request->shift_id;
        $request_id = $request->request_id;

        $sender_id = $id;
        $receiver_id = $case_request->nurse_id;

        $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

        $case_status = HospiceCase::where('id', $case_request->case_id)->first();
        $case_status['shift'] = CaseShift::where('case_id', $case_request->case_id)->where('id', $case_request->shift_id)->first();

        return response()->json([
            "status" => 1,
            "message" => "Case " . $request->status . " successfully!",
            'data' => [
                'case' => $case_status
            ]
        ]);
    }

    /** Re-Assign Shift */

    public function reAssignShift(Request $request)
    {

        $id = Auth::user()->id;
        $data = $request->all();

        $rules = [
            "id" => "required",
            "case_id" => "required",
            "shift_id" => "required",
            "date" => "required"
        ];

        $customMessages = [
            'required' => 'The :attribute field is required.'
        ];

        $validator = Validator::make($data, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $nurse = NurseAvailability::find($request->id)->where('status', 'available');

        if ($nurse) {

            $case = HospiceCase::where('id', $request->case_id)->first();

            if ($case->care_level == 'continuous care') {

                $nurse_id = NurseAvailability::where('id', $request->id)->pluck('user_id')->first();

                $nurse_discipline = User::where('id', $nurse_id)->pluck('discipline')->first();

                $case_discipline = HospiceCase::where('id', $request->case_id)->where('discipline_needed', 'like', "%$nurse_discipline%")->first();

                if ($case_discipline) {

                    $data = [];

                    $shift_exists = CaseShift::where('id', $request->shift_id)->first();

                    if ($shift_exists) {

                        $new_case = $case->replicate();
                        $new_case->status = 'available';
                        $new_case->nurse_id = null;
                        $new_case->start_date = $request->date;
                        $new_case->end_date = $request->date;
                        $new_case->is_sheet_filled = 0;
                        $new_case->is_platform_fee_paid = 0;
                        $new_case->save();

                        $new_shift = $shift_exists->replicate();
                        $new_shift->date = $request->date;
                        $new_shift->shift = 1;
                        $new_shift->nurse_id = $nurse_id;
                        $new_shift->is_sheet_filled = 0;
                        $new_shift->is_platform_fee_paid = 0;
                        $new_shift->save();

                        $send_request = new ShiftRequest();
                        $send_request->nurse_id = $nurse_id;
                        $send_request->availability_id = $request->id;
                        $send_request->hospice_id = $id;
                        $send_request->case_id = $request->case_id;
                        $send_request->shift_id = $request->shift_id;
                        $send_request->status = 'pending';
                        $save = $send_request->save();

                        $data[] = [
                            'request' => $send_request,
                            'case' => $new_case,
                            'shift' => $new_shift
                        ];
                    } else {

                        return response()->json([
                            "status" => 0,
                            "message" => 'Shift Id ' . $shift_id . ' does not exist',
                        ]);
                    }

                    if ($save) {

                        $user = Auth::user();

                        $sender_id = $id;
                        $receiver_id = $nurse_id;
                        $post_id = $request->case_id;
                        $shift_id = $request->shift_id;
                        $request_id = $send_request->id;
                        $type = "Shift Assigned";
                        $message = $user->business_name . " assigned you a shift.";
                        // $notify = new CoreController;
                        $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

                        return response()->json([
                            "status" => 1,
                            "message" => "Shift request has been sent to the nurse!",
                            'data' => $data
                        ]);
                    }
                } else {

                    return response()->json([
                        "status" => 1,
                        "message" => "Nurse's discipline doesn't match with your case!",
                    ]);
                }
            } else {
                return response()->json([
                    "status" => 0,
                    "message" => "This case does not have shifts!",
                ]);
            }
        } else {
            return response()->json([
                "status" => 0,
                "message" => 'Nurse does not exist',
            ]);
        }

        return response()->json([
            "status" => 0,
            "message" => "Failed to assign nurse!",
        ]);
    }

    /** View Details */

    public function viewDetails(Request $request)
    {

        $this->validate($request, [
            'shift_id' => 'required|exists:shifts,id'
        ]);


        $detail = Shifts::with(
            ['case_detail' => function ($detail) {
                return $detail->selectRaw("id,patient_name,location,dob,phone_number,gender,discipline,care_level,case_status,DATE_FORMAT(STR_TO_DATE(start_date, '%Y-%m-%d'), '%m/%d/%Y') AS start_date,DATE_FORMAT(STR_TO_DATE(end_date, '%Y-%m-%d'), '%m/%d/%Y') AS end_date");
            }]
        )->selectRaw("id,case_id,DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
                    TIME_FORMAT(
                        DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                        '%h:%i %p'
                    ) AS end_time,nurse_id")->whereId($request->shift_id)->first();

        return apiSuccessMessage("Detail", $detail);
    }

    /** View Route Sheet */

    public function viewCaseRouteSheets(Request $request)
    {

        $route_sheets = RouteSheet::where('case_id', $request->case_id)->get();
        $case = HospiceCase::where('id', $request->case_id)->first();

        $data = [
            "case_detail" => $case,
            "routesheets" => $route_sheets
        ];

        return response()->json([
            "status" => 1,
            "message" => "route sheets!",
            "data" => $data
        ]);
    }
}
