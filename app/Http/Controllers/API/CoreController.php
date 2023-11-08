<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponser;
use App\Models\CaseRequest;
use App\Models\Conversation;
use App\Models\Feedback;
use App\Models\HelpAndSupport;
use App\Models\HospiceCase;
use App\Models\Notification;
use App\Models\PatientCase;
use App\Models\Rating;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Psy\CodeCleaner\AssignThisVariablePass;

class CoreController extends Controller
{

    use ApiResponser;

    /** Chat Message */

    public function chatMessages()
    {
        $user = Auth::user();

        $c1 = Conversation::leftJoin('users', function ($join) {
            $join->on('conversations.receiver_id', '=', 'users.id');
        })
            ->where('conversations.sender_id', $user->id)
            ->select([
                'conversations.sender_id',
                'conversations.receiver_id',
                'conversations.type',
                'conversations.last_message',
                'conversations.created_at',
                'users.id',
                'users.first_name',
                'users.business_name',
                'users.profile_image',
            ]);

        $c2 = Conversation::leftJoin('users', function ($join) {
            $join->on('conversations.sender_id', '=', 'users.id');
        })
            ->where('conversations.receiver_id', $user->id)
            ->select([
                'conversations.sender_id',
                'conversations.receiver_id',
                'conversations.type',
                'conversations.last_message',
                'conversations.created_at',
                'users.id',
                'users.first_name',
                'users.business_name',
                'users.profile_image',
            ])->union($c1);

        $chat = $c2->select('conversations.sender_id', 'conversations.receiver_id', 'conversations.type', 'conversations.last_message', 'conversations.created_at', 'users.id', 'users.first_name', 'users.business_name', 'users.profile_image')->get();

        $data = [];
        foreach ($chat as $c) {
            $d1 = strtotime($c->created_at);
            $d2 = strtotime(Carbon::now());

            $datediff = $d2 - $d1;
            $days = round($datediff / (60 * 60 * 24));

            if ($days < 1) {
                $day = "Today";
            } elseif ($days == 1) {
                $day = $days . " day ago";
            } else {
                $day = $days . " days ago";
            }

            $data[] = [
                "sender_id" => $c->sender_id,
                "receiver_id" => $c->receiver_id,
                "type" => $c->type,
                "last_message" => $c->last_message,
                "created_at" => $c->created_at,
                "id" => $c->id,
                "first_name" => $c->first_name,
                "business_name" => $c->business_name,
                "profile_image" => $c->profile_image,
                "days" => $day
            ];
        }


        if ($chat->count() > 0) {
            return response()->json([
                "status"    =>  1,
                "message"   =>  "Message found",
                "data"      =>  $data
            ]);
        } else {
            return response()->json([
                "status"    =>  0,
                "message"   =>  "Message not found",
                "data" => $data
            ]);
        }
    }

    /** Notifications */

    public function notifications()
    {
        $user = Auth::user();

        $notifications = Notification::where('receiver_id', $user->id)->with('user:id,first_name,profile_image')->get();

        if ($notifications->count() > 0) {

            return response()->json([
                "status"    =>  1,
                "message"   =>  "Notifications",
                "data"      =>  $notifications
            ], 200);
        } else {
            return response()->json([
                "status"    =>  0,
                "message"   =>  "Notifications not found"
            ], 400);
        }
    }

    /** Add Notification */

    public function add_notification($sender_id, $receiver_id, $type, $message)
    {
        $check = User::where('id', $receiver_id)->first();
        if ($check) {
            $notification = new Notification;
            $notification->sender_id = $sender_id;
            $notification->receiver_id = $receiver_id;
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
                ],
                "notification" => [
                    "title" => 'Hospice',
                    "type" => $type,
                    "body" => $message,
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

    /** Delete Notification */

    public function deleteNotification($id)
    {
        $notification = Notification::where('id', $id);

        if ($notification) {
            $notification->delete();
            return $this->success('Notification deleted successfully.');
        } else {
            return $this->error('Notification not found.', 400);
        }
    }

    public function subscriptions()
    {
        $subscriptions = Subscription::all();

        if ($subscriptions) {

            return response()->json([
                "status"    =>  1,
                "message"   =>  "Subscriptions",
                "data"      =>  $subscriptions
            ], 200);
        } else {
            return response()->json([
                "status"    =>  0,
                "message"   =>  "Subscriptions not found"
            ], 400);
        }
    }

    /** Past Services */

    public function pastServices(Request $request, AssignmentController $assignmentController)
    {
        $this->validate($request, [
            'date' => 'required'
        ]);
        $formattedDate = DateTime::createFromFormat('m/d/Y',  $request->date)->format('Y-m-d');
        // return $formattedDate;
        $cases = PatientCase::
            //     has('shifts')->with(['business_name:id,first_name,last_name,business_name', 'shifts' => function ($shifts) {
            //     return $shifts->selectRaw("id,case_id,nurse_id,
            //                                         DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,
            //                                         TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
            //                                         TIME_FORMAT(
            //                                             DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
            //                                             '%h:%i %p'
            //                                         ) AS end_time,
            //                                         CASE
            //                                         WHEN nurse_id IS NULL THEN ''
            //                                         ELSE 'booked'
            //                                     END AS status,is_sheet_filled,hours_worked");
            // }, 'shifts.nurse:id,first_name,last_name,email,profile_image,rates,discipline,license_no,phone_number'])
            //     ->
            selectRaw("id, user_id,patient_name, location, dob, phone_number, gender, discipline, care_level,
                            case_status,DATE_FORMAT(STR_TO_DATE(start_date, '%Y-%m-%d'), '%m/%d/%Y') AS start_date,
                             DATE_FORMAT(STR_TO_DATE(end_date, '%Y-%m-%d'), '%m/%d/%Y') AS end_date,
                             note,is_patient_died,null as status,null as shift_ids,null as requested,
                             CASE WHEN care_level != 'continuous care' THEN (select TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') from shifts where case_id = patient_cases.id limit 1) ELSE ''  END as start_time,
                             CASE WHEN care_level != 'continuous care' THEN (select TIME_FORMAT(
                                                                             DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                                                                             '%h:%i %p'
                                                                         ) from shifts where case_id = patient_cases.id limit 1) ELSE '' END as end_time
                             ")
            ->selectRaw('CASE WHEN (SELECT COUNT(id) FROM shifts WHERE case_id = patient_cases.id AND nurse_id IS NOT NULL) > 0 THEN 0 ELSE 1 END AS can_edit')
            ->whereUserId(auth()->id())
            ->where('end_date', '<', $formattedDate)
            ->latest('id')
            ->get();
        // $cases = $assignmentController->formatCaseAndShiftsData($cases);

        return apiSuccessMessage("Past Services", $cases);
    }

    public function nursePastServices(Request $request, AssignmentController $assignmentController)
    {

        $nurse_id =  auth()->id();

        $cases = PatientCase::has('booked_shifts')->with(['business_name:id,first_name,last_name,business_name', 'feedback:id,post_id,description,rating', 'case_signature:id,case_id,signature,service_code,hourly_rate,total_amount', 'shifts' => function ($shifts) use ($nurse_id) {
            return $shifts->selectRaw("id,case_id,
                                            DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,
                                            TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
                                            TIME_FORMAT(
                                                DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                                                '%h:%i %p'
                                            ) AS end_time,is_sheet_filled,hours_worked
                                            ")
                ->where('nurse_id', $nurse_id)
                ->where('status',  'completed');
        }])
            ->selectRaw('*,true as past,(SELECT ROUND(AVG(rating), 1) from feedbacks where post_id = patient_cases.id AND user_id = "' . $nurse_id . '") as avg_rating')
            ->whereRaw('( id not in (SELECT case_id FROM shifts where nurse_id = "' . $nurse_id . '" AND status  = "booked" ))')
            ->latest()
            ->get();
        // return $cases;
        $cases = $assignmentController->formatCaseAndShiftsData($cases);
        return apiSuccessMessage("Past Services", $cases);
    }

    /** Complete Feedback */

    public function completeFeedback(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required',
            'post_id' => 'required',
            'description' => 'required',
            'rating' => 'numeric|min:1|max:5'
        ]);

        $data = [
            'user_id' => $request->user_id,
            'post_id' => $request->post_id,
        ];

        $hasAlreadyGivenFeedback = Feedback::where($data)->exists();

        if ($hasAlreadyGivenFeedback) {
            return commonErrorMessage("Feedback already given", 400);
        }


        Feedback::create($data + $request->only(['description', 'rating']));

        return commonSuccessMessage("Success");
    }

    // public function completeFeedback(Request $request)
    // {

    //     $data = $request->all();
    //     $rules = [
    //         "post_id" => "required",
    //         "description" => "required"
    //     ];

    //     $validator = Validator::make($data, $rules);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             "status" => 0,
    //             "message" => $validator->errors()->all()[0],
    //         ]);
    //     }

    //     $save = new Feedback();
    //     $save->user_id = Auth::user()->id;
    //     $save->post_id = $request->post_id;
    //     $save->description = $request->description;
    //     $save->rating = $request->rating;
    //     $save->save();

    //     if ($save) {

    //         $user = auth()->user();

    //         if ($user->role == 'hospice') {

    //             $get_ratings = [];
    //             $nurse_id = HospiceCase::where('id', $request->post_id)->pluck('nurse_id')->first();
    //             $prev_feedbacks = Feedback::with('case:id,nurse_id')->get();
    //             foreach ($prev_feedbacks as $feedback) {

    //                 if ($feedback) {
    //                     if ($feedback->case->nurse_id == $nurse_id) {
    //                         $get_ratings[] = $feedback->rating;
    //                     }
    //                 }
    //             }

    //             $sum = array_sum($get_ratings);
    //             $count = count($get_ratings);
    //             $avg_rating = $sum / $count;

    //             $user_rating = Rating::where('user_id', $nurse_id)->first();
    //             $user_role = User::where('id', $nurse_id)->pluck('role')->first();

    //             if (!$user_rating) {
    //                 $rating = new Rating();
    //                 $rating->user_id = $nurse_id;
    //                 $rating->avg_rating = round($avg_rating, 1);
    //                 $rating->user_role = $user_role;
    //                 $rating->save();
    //             } else {
    //                 $user_rating->update(['avg_rating' => round($avg_rating, 1)]);
    //             }
    //         }

    //         if ($user->role == 'nurse') {
    //             $get_ratings = [];
    //             $hospice_id = HospiceCase::where('id', $request->post_id)->pluck('user_id')->first();
    //             $prev_feedbacks = Feedback::with('case:id,user_id')->get();
    //             foreach ($prev_feedbacks as $feedback) {
    //                 if ($feedback) {
    //                     if ($feedback->case->user_id == $hospice_id) {
    //                         $get_ratings[] = $feedback->rating;
    //                     }
    //                 }
    //             }

    //             $sum = array_sum($get_ratings);
    //             $count = count($get_ratings);
    //             $avg_rating = $sum / $count;

    //             $user_rating = Rating::where('user_id', $hospice_id)->first();
    //             $user_role = User::where('id', $hospice_id)->pluck('role')->first();

    //             if (!$user_rating) {
    //                 $rating = new Rating();
    //                 $rating->user_id = $hospice_id;
    //                 $rating->avg_rating = round($avg_rating, 1);
    //                 $rating->user_role = $user_role;
    //                 $rating->save();
    //             } else {
    //                 $user_rating->update(['avg_rating' => round($avg_rating, 1)]);
    //             }
    //         }

    //         return response()->json([
    //             "status" => 1,
    //             "message" => "Feedback completed successfully!",
    //             'data' => [
    //                 'feedback' => $save
    //             ]
    //         ]);
    //     }
    // }

    /** Help And Support */

    public function helpAndSupport(Request $request)
    {
        $user_id = Auth::user()->id;
        $controls = $request->all();


        $rules = [
            "name" => "required",
            "email" => "required",
            "message" => "required",
        ];
        $validator = Validator::make($controls, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $support = new HelpAndSupport();
        $support->user_id = $user_id;
        $support->name = $request->name;
        $support->email = $request->email;
        $support->message = $request->message;

        if ($support->save()) {
            return response()->json(
                [
                    "status" => 1,
                    "message" => "Your message has been submitted",
                    "support" => $support
                ],
                200
            );
        } else {
            return response()->json(
                [
                    "message" => "Error occured",
                ],
                400
            );
        }
    }

    //Delete Request Cron Job
    public function deleteRequest()
    {
        $case_requests = CaseRequest::get();

        foreach ($case_requests as $case_request) {

            $delete = $case_request->where('created_at', '<', Carbon::parse('-24 hours'))->first();
            $delete->delete();
        }
    }

    public function addStripeCard(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            'card_number' => 'required',
            'cvc' => 'required',
            'expiry_month' => 'required',
            'expiry_year' => 'required',
        );

        $validator = Validator::make($controls, $rules);
        if ($validator->fails()) {
            return response()->json([
                "status" => 0,
                "message" => $validator->errors()->all()[0],
            ]);
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        if ($user->customer_id == null) {

            $customer = $stripe->customers->create([
                'description' => 'My First Test Customer (created for API docs at https://www.stripe.com/docs/api)',
            ]);
            $cus_id = $customer->id;
        } else {
            $cus_id = $user->customer_id;
        }

        // if ($user->card_id == null){
        $card_token = $stripe->tokens->create([
            'card' => [
                'number' => $request->card_number,
                'exp_month' => $request->expiry_month,
                'exp_year' => $request->expiry_year,
                'cvc' => $request->cvc,
            ],
        ]);
        $card = $stripe->customers->createSource(
            $cus_id,
            ['source' => $card_token->id]
        );
        $user = User::find($user->id);
        $user->customer_id = $cus_id;
        $user->card_id = $card->id;
        if ($user->save()) {
            return response()->json([
                "status" => 1,
                "message" => 'Card created successfully',
                'data' => $card
            ]);
        }
        // }
        // else{
        //     return response()->json([
        //         "status" => 0,
        //         "message" => 'Card already created',
        //     ]);
        // }
    }

    public function getCardList()
    {
        $user = Auth::user();
        $customer_id = $user->customer_id;
        if ($user->customer_id == null) {
            return response()->json([
                "status" => 0,
                "message" => 'Create customer first',
            ]);
        }

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $list = $stripe->customers->allSources(
            $customer_id,
            [
                'object' => 'card',
                'limit' => 4,
            ]
        );

        if (count($list) > 0) {
            return response()->json([
                "status" => 1,
                "message" => 'Card list found successfully',
                'data' => $list->data
            ]);
        } else {
            return response()->json([
                "status" => 0,
                "message" => 'Not found',
            ]);
        }
    }

    public function deleteCard($card_id)
    {

        $user = Auth::user();

        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));

        // $ret = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $list = $stripe->customers->allSources(
            $user->customer_id,
            [
                'object' => 'card',
                'limit' => 4,
            ]
        );

        foreach ($list->data as $card) {
            if ($card->id == $card_id) {
                $delete = $stripe->customers->deleteSource(
                    $user->customer_id,
                    $card_id,
                    []
                );

                $user->is_card = 0;
                $user->save();

                return response()->json([
                    "status" => 1,
                    "message" => 'Card deleted successfully'
                ]);
            }
        }



        // if($delete->deleted == true)
        // {
        //     return response()->json([
        //             "status" => 1,
        //             "message" => 'Card deleted successfully'
        //         ]);
        // }
        // else
        // {
        return response()->json([
            "status" => 0,
            "message" => 'Card already deleted',
        ]);
        // }

    }
}
