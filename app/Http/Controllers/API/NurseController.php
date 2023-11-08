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
use App\Models\PatientCase;
use App\Models\RouteSheet;
use App\Models\ShiftRequest;
use App\Models\Shifts;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use DateTime;
use DateInterval;
use DatePeriod;
use function Sodium\add;

class NurseController extends Controller
{
    use ApiResponser;

    /** Availability List */

    public function availableNurses(Request $request)
    {
        $case_id = $request->case_id;
        $nurses = User::selectRaw('id,first_name,last_name,email,phone_number,license_no,discipline,rates,address,profile_image,
                                    (SELECT ROUND(AVG(rating), 1) from feedbacks where user_id= users.id) as avg_rating,is_approved')
            ->whereRole('nurse')
            ->whereIsProfileComplete(true)
            ->when($case_id, function ($q) use ($case_id) {
                $discipline = PatientCase::whereId($case_id)->select('discipline')->first()?->discipline;
                $q->where(function ($q) use ($discipline) {
                    return $q->where('discipline', $discipline);
                })
                    ->whereNotIn('id', ShiftRequest::where('case_id', '!=', $case_id)->where('status', 'accepted')->pluck('nurse_id'));
            })
            ->get();
        return apiSuccessMessage("Nurses", $nurses);
    }

    /** Create Availability */

    public function createAvailability(Request $request)
    {

        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "monday" => "nullable",
            "tuesday" => "nullable",
            "wednesday" => "nullable",
            "thursday" => "nullable",
            "friday" => "nullable",
            "saturday" => "nullable",
            "sunday" => "nullable"
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $availabilities = NurseAvailability::where('user_id', $id)->get();
        if ($availabilities) {
            foreach ($availabilities as $availability)
                $availability::where('date', null)->where('user_id', $id)->delete();
        }

        $days = [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday'
        ];

        foreach ($days as $day) {
            for ($i = 0; $i < count($request->input($day)); $i++) {
                $save = new NurseAvailability();
                $save->user_id = $id;
                $save->start_shift_time = $request->input($day)[$i]['start_shift_time'];
                $save->end_shift_time = $request->input($day)[$i]['end_shift_time'];
                $save->day = $day;
                $save->status = $request->input($day)[$i]['status'];
                $save->save();
            }
        }

        $mon = $tue = $wed = $thur = $fri = $sat = $sun = [];
        foreach ($days as $day) {
            $updated_availabilities = NurseAvailability::select("id", 'user_id', 'start_shift_time', "end_shift_time", "date", "status")->with('nurse_details:id,first_name,last_name,email,address,rates,discipline,profile_image')
                ->where('user_id', $id)->where('day', $day)->get();

            if ($day == 'monday') {
                $mon = $updated_availabilities;
            }
            if ($day == 'tuesday') {
                $tue = $updated_availabilities;
            }
            if ($day == 'wednesday') {
                $wed = $updated_availabilities;
            }
            if ($day == 'thursday') {
                $thur = $updated_availabilities;
            }
            if ($day == 'friday') {
                $fri = $updated_availabilities;
            }
            if ($day == 'saturday') {
                $sat = $updated_availabilities;
            }
            if ($day == 'sunday') {
                $sun = $updated_availabilities;
            }
        }

        $format = [
            "monday" => $mon,
            "tuesday" => $tue,
            "wednesday" => $wed,
            "thursday" => $thur,
            "friday" => $fri,
            "saturday" => $sat,
            "sunday" => $sun
        ];

        if ($save) {
            return response()->json([
                "status" => 1,
                "message" => "Availability created successfully!",
                'data' => [
                    'availability' => $format,
                ]
            ]);
        }
        return response()->json([
            "status" => 0,
            "message" => "Failed to add availability!",
        ]);
    }

    /** Create Specific Availability*/

    public function createSpecificAvailability(Request $request)
    {

        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "start_shift_time" => "required",
            "end_shift_time" => "required",
            "day" => "required",
            "date" => "required",
            "status" => "required"
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $check = NurseAvailability::where('user_id', $id)->where('date', $request->date)->first();
        if ($check) {
            return response()->json([
                "status" => 0,
                "message" => "Already available."
            ]);
        }

        $save = new NurseAvailability();
        $save->user_id = $id;
        $save->start_shift_time = $request->start_shift_time;
        $save->end_shift_time = $request->end_shift_time;
        $save->day = $request->day;
        $save->date = $request->date;
        $save->status = $request->status;
        $save->save();

        $updated_availability = NurseAvailability::where('id', $save->id)->with('nurse_details:id,first_name,last_name,email,address,rates,discipline,profile_image')
            ->where('user_id', $id)->get();

        if ($save) {
            return response()->json([
                "status" => 1,
                "message" => "Availability created successfully!",
                'data' => [
                    'availability' => $updated_availability,
                ]
            ]);
        }
        return response()->json([
            "status" => 0,
            "message" => "Failed to add availability!",
        ]);
    }


    public function getSpecificAvailability(Request $request)
    {

        // $date =  $request->date;

        // if (!($status == 'available' || $status == 'unavailable' || $status == 'assigned')){
        //     return response()->json([
        //         "status" => 0,
        //         "message" => "Enter a valid status"
        //     ]);
        // }else {

        $id = Auth::user()->id;

        $availabilities = NurseAvailability::where('user_id', $id)->where('date', '!=', null)->where('status', 'available')->get();
        foreach ($availabilities as $av) {
            $av["date"] = date('m/d/Y', strtotime($av->date));
        }

        $data = [
            'availabilities' => $availabilities
        ];

        return response()->json([
            'status' => 1,
            'message' => 'Specific date availability!',
            'data' => $data,
        ]);
        // }

    }


    //Delete SpecificAvailability
    public function deleteSpecificAvailability()
    {
        $today = Carbon::today();

        $availabilities = NurseAvailability::where('date', '!=', null)->where('date', '<', $today)->delete();
        if ($availabilities) {
            echo "deleted";
        } else {
            echo "Not Found";
        }
    }

    /** Accept Case */

    public function acceptCase(Request $request)
    {
        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "id" => "required",
            "shift_ids" => "nullable",
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $has_shifts = CaseShift::where('case_id', $request->id)->first();

        if ($has_shifts) {

            if ($request->has('shift_ids')) {

                $nurse_shifts = CaseShift::where('nurse_id', $id)->get();

                foreach ($nurse_shifts as $nurse_shift) {

                    $nurse_shift_case = HospiceCase::where('id', $nurse_shift->case_id)->first();

                    if ($nurse_shift_case->status == 'inprocess') {

                        return response()->json([
                            "status" => 0,
                            "message" => "You have a case in process"
                        ]);
                    }

                    if ($nurse_shift->status == 'completed') {
                        $route_sheet = RouteSheet::where('shift_id', $nurse_shift->id)->first();
                        if (!$route_sheet) {
                            return response()->json([
                                "status" => 0,
                                "message" => "Fill the route sheet of your previous case first"
                            ]);
                        }
                    }
                }

                $nurse_name = User::where('id', $id)->first();

                $case = HospiceCase::where('id', $request->id)->where('status', 'available')->first();

                if ($case) {

                    $request_ids = [];

                    foreach ($request->shift_ids as $shift_id) {

                        $approval = new CaseApproval();
                        $approval->hospice_id = $case->user_id;
                        $approval->case_id = $request->id;
                        $approval->shift_id = $shift_id;
                        $approval->nurse_id = $id;
                        $approval->status = 'unapproved';

                        $save = $approval->save();

                        $request_ids[] = $approval->id;

                        if ($save) {

                            $sender_id = $id;
                            $receiver_id = $case->user_id;
                            $post_id = $request->id;
                            $shift_id = null;
                            $request_id = $approval->id;
                            $type = "Case Request";
                            $message =  $nurse_name->first_name . " has send you a case request.";
                            //$notify = new CoreController;
                            $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);
                        }
                    }

                    if ($save) {

                        return response()->json([
                            "status" => 1,
                            "message" => "Case Request Sent successfully!",
                            'data' => [
                                'case' => $case,
                                'request_ids' => $request_ids
                            ]
                        ]);
                    }
                }
            } else {

                return response()->json([
                    "status" => 0,
                    "message" => "Shift Id/s required!",
                ]);
            }
        } else {

            $nurse_cases = HospiceCase::where('nurse_id', $id)->get();

            foreach ($nurse_cases as $nurse_case) {

                if ($nurse_case->status == 'inprocess') {

                    return response()->json([
                        "status" => 0,
                        "message" => "You have a case in process"
                    ]);
                } elseif ($nurse_case->status == 'completed') {

                    $route_sheet = RouteSheet::where('case_id', $nurse_case->id)->first();

                    if (!$route_sheet) {
                        return response()->json([
                            "status" => 0,
                            "message" => "Fill the route sheet of your previous case first"
                        ]);
                    }
                }
            }

            $nurse_name = User::where('id', $id)->first();

            $case = HospiceCase::where('id', $request->id)->where('status', 'available')->first();

            if ($case) {

                $approval = new CaseApproval();
                $approval->hospice_id = $case->user_id;
                $approval->case_id = $request->id;
                $approval->nurse_id = $id;
                $approval->status = 'unapproved';

                $save = $approval->save();

                if ($save) {

                    $sender_id = $id;
                    $receiver_id = $case->user_id;
                    $post_id = $request->id;
                    $shift_id = null;
                    $request_id = $approval->id;
                    $type = "Case Request";
                    $message =  $nurse_name->first_name . " has send you a case request.";
                    //$notify = new CoreController;
                    $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

                    return response()->json([
                        "status" => 1,
                        "message" => "Case Request Sent successfully!",
                        'data' => [
                            'case' => $case,
                            'request_id' => $approval->id
                        ]
                    ]);
                }
            }
        }
    }

    // /** Accept Case Duplicate */

    // public function acceptCaseDuplicate(Request $request)
    // {
    //     $id = Auth::user()->id;

    //     $nurse_name = User::where('id', $id)->first();

    //     $data = $request->all();

    //     $rules = [
    //         "id" => "required",
    //         "shift_ids" => "nullable",
    //     ];

    //     $validator = Validator::make($data, $rules);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             "status" => 0,
    //             "message" => $validator->errors()->all()[0],
    //         ]);
    //     }

    //     $any_assigned_case = HospiceCase::where('nurse_id', $id)->where('status', 'inprocess')->first();

    //     $any_assigned_shift = CaseShift::where('nurse_id', $id)->where('status', 'inprocess')->first();

    //     $any_remaining_case_routesheet = HospiceCase::where('nurse_id', $id)->where('status', 'completed')->where('is_sheet_filled', 0)->where('is_platform_fee_paid', 0)->first();

    //     dd($any_assigned_shift);
    //     // $any_completed_case_routesheet_not_filled = CaseShift::where('case_id', function($q){
    //     //     $q->select('id')->from(with(new HospiceCase)->getTable())
    //     //     ->where('status','completed');
    //     // })->where('nurse_id', $id)->where('status', 'completed')->where('is_sheet_filled', 0)->orWhere('is_platform_fee_paid', 0)->first();

    //     if($any_assigned_case){
    //         dd('any_assigned_case');

    //         return response()->json([
    //             "status" => 0,
    //             "message" => "You have a case in process"
    //         ]);

    //     }

    //     elseif($any_remaining_case_routesheet){
    //         dd('any_remaining_case_routesheet');

    //         return response()->json([
    //             "status" => 0,
    //             "message" => "Fill the route sheet of your previous case first"
    //         ]);

    //     }

    //     // elseif($any_completed_case_routesheet_not_filled){
    //     //     dd($any_completed_case_routesheet_not_filled);

    //     //     return response()->json([
    //     //         "status" => 0,
    //     //         "message" => "Fill all the route sheets of your previous case first"
    //     //     ]);

    //     // }

    //     $case = HospiceCase::where('id' , $request->id)->first();

    //     if($any_assigned_shift){

    //          if($any_assigned_shift->case_id == $request->id){

    //             $request_ids = [];

    //             foreach ($request->shift_ids as $shift_id){

    //                 $approval = new CaseApproval();
    //                 $approval->hospice_id = $case->user_id;
    //                 $approval->case_id = $request->id;
    //                 $approval->shift_id = $shift_id;
    //                 $approval->nurse_id = $id;
    //                 $approval->status = 'unapproved';

    //                 $save = $approval->save();

    //                 $request_ids[] = $approval->id;

    //                 if ($save) {

    //                     $sender_id = $id;
    //                     $receiver_id = $case->user_id;
    //                     $post_id = $request->id;
    //                     $shift_id = null;
    //                     $request_id = $approval->id;
    //                     $type = "Case Request";
    //                     $message =  $nurse_name->first_name . " has send you a case request.";
    //                     //$notify = new CoreController;
    //                     $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

    //                 }

    //             }

    //             if ($save) {

    //                 return response()->json([
    //                     "status" => 1,
    //                     "message" => "Case Request Sent successfully!",
    //                     'data' => [
    //                         'case' => $case,
    //                         'request_ids' => $request_ids
    //                     ]
    //                 ]);
    //             }

    //         }else{

    //                     return response()->json([
    //                     "status" => 0,
    //                     "message" => "You have a case in process"
    //                     ]);

    //         }

    //     }


    //     if($request->has('shift_ids')){
    //         dd('shift');

    //       $request_ids = [];

    //         foreach ($request->shift_ids as $shift_id){

    //             $approval = new CaseApproval();
    //             $approval->hospice_id = $case->user_id;
    //             $approval->case_id = $request->id;
    //             $approval->shift_id = $shift_id;
    //             $approval->nurse_id = $id;
    //             $approval->status = 'unapproved';

    //             $save = $approval->save();

    //             $request_ids[] = $approval->id;

    //             if ($save) {

    //                 $sender_id = $id;
    //                 $receiver_id = $case->user_id;
    //                 $post_id = $request->id;
    //                 $shift_id = null;
    //                 $request_id = $approval->id;
    //                 $type = "Case Request";
    //                 $message =  $nurse_name->first_name . " has send you a case request.";
    //                 //$notify = new CoreController;
    //                 $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

    //             }

    //         }

    //         if ($save) {

    //             return response()->json([
    //                 "status" => 1,
    //                 "message" => "Case Request Sent successfully!",
    //                 'data' => [
    //                     'case' => $case,
    //                     'request_ids' => $request_ids
    //                 ]
    //             ]);
    //         }

    //     }else{

    //         $approval = new CaseApproval();
    //         $approval->hospice_id = $case->user_id;
    //         $approval->case_id = $request->id;
    //         $approval->nurse_id = $id;
    //         $approval->status = 'unapproved';

    //         $save = $approval->save();

    //         if ($save) {

    //             $sender_id = $id;
    //             $receiver_id = $case->user_id;
    //             $post_id = $request->id;
    //             $shift_id = null;
    //             $request_id = $approval->id;
    //             $type = "Case Request";
    //             $message =  $nurse_name->first_name . " has send you a case request.";
    //             //$notify = new CoreController;
    //             $this->add_notification($sender_id, $receiver_id,$post_id, $shift_id, $request_id, $type, $message);

    //             return response()->json([
    //                 "status" => 1,
    //                 "message" => "Case Request Sent successfully!",
    //                 'data' => [
    //                     'case' => $case,
    //                     'request_id' => $approval->id
    //                 ]
    //             ]);
    //         }

    //     }


    // }

    /** Complete Case */

    public function completeCase(Request $request)
    {

        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "id" => "required",
            "shift_id" => "nullable",
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $case = HospiceCase::where('id', $request->id)->first();

        if ($case) {

            $has_shifts = CaseShift::where('case_id', $request->id)->first();

            if ($has_shifts) {

                if ($request->has('shift_id')) {

                    $shift = CaseShift::where('case_id', $request->id)->where('id', $request->shift_id)->first();

                    $shift->status = 'completed';

                    $save = $shift->save();

                    $case['shift'] = $shift;

                    if ($save) {
                        return response()->json([
                            "status" => 1,
                            "message" => "Shift completed successfully!",
                            'data' => [
                                'case' => $case
                            ]
                        ]);
                    }
                } else {

                    return response()->json([
                        "status" => 0,
                        "message" => "Shift Id required!",
                    ]);
                }
            } else {

                if ($case->status == 'inprocess') {

                    $case->nurse_id = $id;
                    $case->status = 'completed';

                    $save = $case->save();

                    if ($save) {
                        return response()->json([
                            "status" => 1,
                            "message" => "Case completed successfully!",
                            'data' => [
                                'case' => $case
                            ]
                        ]);
                    }
                } else {

                    return response()->json([
                        "status" => 0,
                        "message" => "Case is not available!",
                    ]);
                }
            }
        } else {
            return response()->json([
                "status" => 0,
                "message" => "Case does not exist!",
            ]);
        }
    }

    /** Cancel Case */


    public function cancelCase(Request $request)
    {

        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "id" => "required",
            "reason" =>  "required",
            "shift_id" => "nullable"
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $case = HospiceCase::where('id', $request->id)->first();

        if ($case) {

            $has_shifts = CaseShift::where('case_id', $request->id)->first();

            if ($has_shifts) {

                if ($request->has('shift_id')) {

                    $shift = CaseShift::whereIn('id', $request->shift_id)->update(['status' => 'cancelled', 'nurse_id' => $id]);

                    $case['shift'] = $shift;

                    $unapprove_all_case = CaseApproval::where('nurse_id', $id)->where('case_id', $request->id)->whereIn('shift_id', $request->shift_id)->update(['status' => 'unapproved']);

                    if ($shift) {
                        return response()->json([
                            "status" => 1,
                            "message" => "Shift/s cancelled successfully!",
                            'data' => [
                                'case' => $case
                            ]
                        ]);
                    }
                } else {

                    return response()->json([
                        "status" => 0,
                        "message" => "Shift Id required!",
                    ]);
                }
            } else {

                if ($case->status == 'inprocess') {

                    $case->nurse_id = null;
                    $case->status = "available";
                    $case->cancel_reason = $request->reason;
                    $save = $case->save();

                    $unapprove_all_case = CaseApproval::where('nurse_id', $id)->where('case_id', $request->id)->update(['status' => 'unapproved']);

                    $availabilities = NurseAvailability::where('user_id', $id)->get();

                    foreach ($availabilities as $availability) {
                        $availability->status = 'available';
                        $availability->save();
                    }

                    if ($save) {
                        return response()->json([
                            "status" => 1,
                            "message" => "Case cancelled successfully!",
                            'data' => [
                                'case' => $case
                            ]
                        ]);
                    }
                }
            }
        } else {
            return response()->json([
                "status" => 0,
                "message" => "Case is not available!",
            ]);
        }
    }

    /** Route Sheet List */

    public function routeSheetList(Request $request)
    {

        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "case_id" => "required"
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $route_sheets = RouteSheet::where('user_id', $id)->where('case_id', $request->case_id)->orderBy('case_id')->get();
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

    /** Create Route Sheet */

    public function createRouteSheet(Request $request)
    {

        $user = Auth::user();

        $data = $request->all();

        $rules = [
            "case_id" => "required|exists:hospice_cases,id",
            "shift_id" => "nullable",
            "staff_name" => "required",
            "date" => "nullable",
            "patient_name" => "required",
            "signature" => "required",
            "service_code" => "required",
            "time_begin" => "required",
            "time_end" => "required",
            "hours_worked" => "required",
            "hourly_rate" => "required",
            "amount" => "required"
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $shift = CaseShift::where('id', $request->shift_id)->first();

        if ($shift) {

            if ($request->has('shift_id')) {

                if ($shift->nurse_id == $user->id) {

                    if ($shift->is_sheet_filled == 0) {

                        $current_date = $request->date;

                        $current_time = Carbon::now()->format('H:i:s');

                        $check_time = $shift->shift == 1 ? '12:00:00' : '00:00:00';

                        if ($current_date > $shift->date && strtotime($current_time) > strtotime($check_time)) {

                            $route_sheet = new RouteSheet();
                            $route_sheet->user_id = $user->id;
                            $route_sheet->case_id = $request->case_id;
                            $route_sheet->shift_id = $shift->id;
                            $route_sheet->date = $request->date;
                            $route_sheet->staff_name = $request->staff_name;
                            $route_sheet->patient_name = $request->patient_name;
                            $route_sheet->signature = $request->signature;
                            $route_sheet->service_code = $request->service_code;
                            $route_sheet->time_in = $request->time_begin;
                            $route_sheet->time_out = $request->time_end;
                            $route_sheet->hours_worked = $request->hours_worked;
                            $route_sheet->hourly_rate = $request->hourly_rate;
                            $route_sheet->amount = $request->amount;
                            $route_sheet->save();

                            $shift->is_sheet_filled = 1;
                            $shift->save();

                            $check_shifts = CaseShift::where('case_id', $request->case_id)->where('nurse_id', $user->id)->where('status', 'completed')->get();

                            $check = '';


                            foreach ($check_shifts as $check_shift) {

                                if ($check_shift->is_sheet_filled == 1) {

                                    $check = 1;
                                } else {

                                    $check = 0;
                                }
                            }

                            if ($check == 0) {


                                return response()->json([
                                    "status" => 1,
                                    "message" => "Route sheet completed successfully!",
                                    'data' => [
                                        'route_sheet' => $route_sheet
                                    ]
                                ]);
                            } else {

                                $fee_paid = CaseShift::where('case_id', $request->case_id)->where('nurse_id', $user->id)
                                    ->where('is_platform_fee_paid', 1)->first();

                                if ($fee_paid) {

                                    return response()->json([
                                        "status" => 1,
                                        "message" => "Platform fee has already been charged!",
                                    ], 200);
                                } else {

                                    $existing_route_sheet = RouteSheet::where('user_id', $user->id)->where('case_id', $request->case_id)->get();

                                    $hours_worked = [];
                                    $total_pay = [];
                                    foreach ($existing_route_sheet as $s) {
                                        $hours_worked[] = $s->hours_worked;
                                        $total_pay[] = $s->amount;
                                    }

                                    $percent = array_sum($total_pay) / 100 * 5;

                                    if (Auth::user()->card_id == null) {
                                        return response()->json([
                                            "status" => 0,
                                            "message" => "Please active your card!",
                                        ]);
                                    } else {

                                        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
                                        $platform_fee = $stripe->charges->create([
                                            'amount' => $percent * 100,
                                            'currency' => 'USD',
                                            'customer' => $user->customer_id,
                                            'source' => $user->card_id,
                                            'description' => 'My First Test Charge (created for API docs at https://www.stripe.com/docs/api)',
                                        ]);

                                        if ($platform_fee) {

                                            $case_shifts = CaseShift::where('case_id', $request->case_id)->get();

                                            foreach ($case_shifts as $cshift) {

                                                if (!$cshift->is_platform_fee_paid == 0) {

                                                    $availabilities = NurseAvailability::where('user_id', $user->id)->get();
                                                    foreach ($availabilities as $availability) {
                                                        $availability->status = 'available';
                                                        $availability->save();
                                                    }
                                                }
                                            }

                                            $nurse_shifts = CaseShift::where('case_id', $request->case_id)->where('nurse_id', $user->id)->get();

                                            foreach ($nurse_shifts as $n_shift) {

                                                $n_shift->is_platform_fee_paid = 1;
                                                $n_shift->save();
                                            }

                                            $check_all_shift_sheets_filled = CaseShift::where('case_id', $request->case_id)->where('status', 'completed')->get();

                                            $check_case = '';

                                            foreach ($check_all_shift_sheets_filled as $check_all_shift_sheet) {

                                                if ($check_all_shift_sheet->is_sheet_filled == 1) {

                                                    $check_case = 1;
                                                } else {

                                                    $check_case = 0;
                                                }
                                            }

                                            if ($check_case == 1) {

                                                $mark_case_completed = HospiceCase::where('id', $request->case_id)->update(['status' => 'completed', 'is_sheet_filled' => '1', 'is_platform_fee_paid' => '1']);
                                            }

                                            return response()->json([
                                                "status" => 1,
                                                "message" => "Platform fee has been charged!",
                                                'data' => [
                                                    'route_sheet' => $existing_route_sheet
                                                ]
                                            ], 200);
                                        }

                                        return response()->json([
                                            "status" => 1,
                                            "message" => "Platform fee has already been charged!",
                                        ], 200);
                                    }
                                }
                            }
                        } else {

                            return response()->json([
                                "status" => 1,
                                "message" => "You can complete routesheet after the shift end date and time!",
                            ], 200);
                        }
                    } else {

                        return response()->json([
                            "status" => 1,
                            "message" => "Route Sheet already filled!",
                        ], 200);
                    }
                } else {

                    return response()->json([
                        "status" => 1,
                        "message" => "This shift is not assigned to you!",
                    ], 200);
                }
            } else {

                return response()->json([
                    "status" => 0,
                    "message" => "Shift Id required",
                ], 200);
            }
        } else {

            $case = HospiceCase::where('id', $request->case_id)->first();

            if ($case->nurse_id == $user->id) {

                if ($case->is_sheet_filled == 0) {

                    $current_date = $request->date;

                    $current_time = Carbon::now()->format('H:i:s');

                    $check_time = $case->close_shift;

                    if ($current_date > $case->end_date && strtotime($current_time) > strtotime($check_time)) {

                        $route_sheet = new RouteSheet();
                        $route_sheet->user_id = $user->id;
                        $route_sheet->case_id = $request->case_id;
                        $route_sheet->date = $request->date;
                        $route_sheet->staff_name = $request->staff_name;
                        $route_sheet->patient_name = $request->patient_name;
                        $route_sheet->signature = $request->signature;
                        $route_sheet->service_code = $request->service_code;
                        $route_sheet->time_in = $request->time_begin;
                        $route_sheet->time_out = $request->time_end;
                        $route_sheet->hours_worked = $request->hours_worked;
                        $route_sheet->hourly_rate = $request->hourly_rate;
                        $route_sheet->amount = $request->amount;
                        $route_sheet->save();

                        $case->is_sheet_filled = 1;
                        $case->save();

                        $fee_paid = HospiceCase::where('id', $request->case_id)->where('nurse_id', $user->id)
                            ->where('is_platform_fee_paid', 1)->first();

                        if ($fee_paid) {

                            return response()->json([
                                "status" => 1,
                                "message" => "Platform fee has already been charged!",
                            ], 200);
                        } else {

                            $percent = $route_sheet->amount / 100 * 5;

                            if (Auth::user()->card_id == null) {
                                return response()->json([
                                    "status" => 0,
                                    "message" => "Please active your card!",
                                ]);
                            } else {

                                $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
                                $platform_fee = $stripe->charges->create([
                                    'amount' => $percent * 100,
                                    'currency' => 'USD',
                                    'customer' => $user->customer_id,
                                    'source' => $user->card_id,
                                    'description' => 'My First Test Charge (created for API docs at https://www.stripe.com/docs/api)',
                                ]);

                                if ($platform_fee) {

                                    $availabilities = NurseAvailability::where('user_id', $user->id)->get();
                                    foreach ($availabilities as $availability) {
                                        $availability->status = 'available';
                                        $availability->save();
                                    }

                                    return response()->json([
                                        "status" => 1,
                                        "message" => "Platform fee has been charged!",
                                        'data' => [
                                            'route_sheet' => $route_sheet
                                        ]
                                    ], 200);
                                }
                            }
                        }
                    } else {

                        return response()->json([
                            "status" => 0,
                            "message" => "You can complete routesheet after the case end date and time!",
                        ], 200);
                    }
                } else {

                    return response()->json([
                        "status" => 1,
                        "message" => "Route Sheet already filled!",
                    ], 200);
                }
            } else {

                return response()->json([
                    "status" => 1,
                    "message" => "This case is not assigned to you!",
                ], 200);
            }
        }
    }

    /** User Availabilities */

    public function userAvailabilities(Request $request)
    {

        $status =  $request->status;

        if (!($status == 'available' || $status == 'unavailable' || $status == 'assigned')) {
            return response()->json([
                "status" => 0,
                "message" => "Enter a valid status"
            ]);
        } else {

            $id = Auth::user()->id;

            $availabilities = NurseAvailability::where('user_id', $id)->where('status', $status)->get();

            $data = [
                'availabilities' => $availabilities
            ];

            return response()->json([
                'status' => 1,
                'message' => 'nurse availabilities!',
                'data' => $data,
            ]);
        }
    }

    /** User Requests */

    public function requestList(Request $request)
    {

        $id = Auth::user()->id;

        $case_requests = CaseRequest::where('nurse_id', $id)->with('hospice_details')->with('case_details')->first();
        $shift_requests = ShiftRequest::where('nurse_id', $id)->with('case_details')->with('hospice_details')->with('shift_details')->first();

        $data = [
            'case_requests' => $case_requests,
            'shift_requests' => $shift_requests,
        ];

        return response()->json([
            'status' => 1,
            'message' => 'nurse requests!',
            'data' => $data,
        ]);
    }

    /** Accept Request */

    public function acceptRequest(Request $request)
    {
        $id = Auth::user()->id;
        $data = $request->all();
        $rules = [
            "id" => "required",
            "shift_id" => "nullable",
            "status" => "required",
            "notification_id" => "nullable"
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $delete_notification = Notification::where('id', $request->notification_id)->delete();

        if ($request->has('shift_id')) {

            $shift_request = ShiftRequest::where('id', $request->id)->first();

            if ($shift_request) {

                $shift_request->status = $request->status;
                $save = $shift_request->save();

                $shift = CaseShift::where('id', $request->shift_id)->first();

                if ($shift) {

                    if ($request->status == 'accepted') {

                        $shift->status = 'inprocess';
                        $shift->nurse_id = $id;
                        $shift->save();

                        /*$availabilities = NurseAvailability::where('user_id', $id)->get();
                        foreach ($availabilities as $availability) {
                            $availability->status = 'unavailable';
                            $availability->save();
                        }*/
                    }
                } else {

                    return response()->json([
                        "status" => 0,
                        "message" => "Shift Id " . $request->shift_id . " does not exist!",
                    ]);
                }



                $nurse_name = User::where('id', $id)->first();

                if ($save) {

                    $post_id = $shift_request->case_id;
                    $shift_id = $request->shift_id;
                    $request_id = null;
                    $sender_id = $id;
                    $receiver_id = $shift_request->hospice_id;
                    $message = '';

                    if ($request->status == 'accepted') {
                        $type = "Case Accepted";
                        $message = $nurse_name->first_name . " has accepted your case.";
                    } elseif ($request->status == 'rejected') {
                        $type = "Case Rejected";
                        $message = $nurse_name->first_name . " has rejected your case.";

                        $delete_request = $shift_request->delete();
                    }

                    // $notify = new CoreController;
                    $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

                    $case_status = HospiceCase::where('id', $request->id)->first();
                    $case_status['shift'] = CaseShift::where('id', $request->shift_id)->first();

                    return response()->json([
                        "status" => 1,
                        "message" => "Case " . $request->status . " successfully!",
                        'data' => [
                            'case' => $case_status
                        ]
                    ]);
                }
            } else {
                return response()->json([
                    "status" => 0,
                    "message" => "Request not found!",

                ], 400);
            }
        } else {

            if ($request->status == 'accepted') {

                $case_request = CaseRequest::where('id', $request->id)->first();

                if ($case_request) {

                    $case_request->status = $request->status;
                    $save = $case_request->save();

                    $case = HospiceCase::where('id', $case_request->case_id)->first();

                    $case->status = 'inprocess';
                    $case->save();

                    $availabilities = NurseAvailability::where('user_id', $id)->get();

                    foreach ($availabilities as $availability) {
                        $availability->status = 'unavailable';
                        $availability->save();
                    }

                    $nurse_name = User::where('id', $id)->first();

                    if ($save) {

                        $post_id = $case_request->case_id;
                        $shift_id = null;
                        $request_id = null;
                        $sender_id = $id;
                        $receiver_id = $case_request->hospice_id;

                        $type = "Case Accepted";
                        $message = $nurse_name->first_name . " has accepted your case.";


                        // $notify = new CoreController;
                        $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

                        return response()->json([
                            "status" => 1,
                            "message" => "Case " . $request->status . " successfully!",
                            'data' => [
                                'case' => $case
                            ]
                        ]);
                    }
                } else {
                    return response()->json([
                        "status" => 0,
                        "message" => "Request not found!",

                    ], 400);
                }
            } else {

                $case_request = CaseRequest::where('id', $request->id)->first();

                if ($case_request) {
                    $nurse_name = User::where('id', $id)->first();

                    $case = HospiceCase::where('id', $case_request->case_id)->first();

                    $post_id = $case_request->case_id;
                    $shift_id = null;
                    $request_id = null;
                    $sender_id = $id;
                    $receiver_id = $case_request->hospice_id;

                    $type = "Case Rejected";
                    $message = $nurse_name->first_name . " has rejected your case.";

                    $delete_case = HospiceCase::where('id', $case_request->case_id)->delete();


                    // $notify = new CoreController;
                    $this->add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message);

                    $delete_request = $case_request->delete();
                    return response()->json([
                        "status" => 1,
                        "message" => "Case " . $request->status . " successfully!",
                        'data' => [
                            'case' => $case
                        ]
                    ]);
                } else {
                    return response()->json([
                        "status" => 0,
                        "message" => "Request not found!",

                    ], 400);
                }
            }
        }
    }


    /** Add Notification */

    public function add_notification($sender_id, $receiver_id, $post_id, $shift_id, $request_id, $type, $message, $nurse_id = 0, $case_id = 0)
    {
        //   dd($receiver_id);
        $check = User::where('id', $receiver_id)->first();
        if ($check) {
            $notification = new Notification;
            $notification->sender_id = $sender_id;
            $notification->receiver_id = $receiver_id;
            $notification->post_id = $post_id;
            $notification->shift_id = $shift_id;
            $notification->request_id = $request_id;

            $notification->nurse_id = $nurse_id;
            $notification->case_id = $case_id;

            $notification->type = $type;
            $notification->message = $message;
            $notification->save();

            //Push Notification
            $firebaseToken = User::where('id', $receiver_id)->first();
            $SERVER_API_KEY = 'AAAAQn8vSX4:APA91bETrBTfRFu7obreUQ89FnRhMwXvHX2q_EmQBFlEsU3PtL-wvWQYbKWDmDedhVKgeNFPKUbLgc0qUkkklXyuVNJ-PXY8JKjH9E4twnlVYodWczocT6PviJNh1_2A2PhbwYCMowyW';

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
}
