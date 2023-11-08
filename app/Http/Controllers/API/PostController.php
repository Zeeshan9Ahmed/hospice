<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CancelledShift;
use App\Models\CancelReason;
use App\Models\Notification;
use App\Models\PatientCase;
use App\Models\ShiftRequest;
use App\Models\Shifts;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use DateTime;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{

    public function __construct(public AssignmentController $assignmentController)
    {
    }

    function getTimeDifferenceToUTC($region)
    {
        // Create DateTimeZone object for the given region
        $regionTimeZone = new DateTimeZone($region);

        // Create DateTimeZone object for UTC
        $utcTimeZone = new DateTimeZone('UTC');

        // Create DateTime objects with current time in the given region and UTC
        $regionTime = new DateTime('now', $regionTimeZone);
        $utcTime = new DateTime('now', $utcTimeZone);

        // Calculate the time difference in hours
        $timeDifference = $regionTime->getOffset() / 3600 - $utcTime->getOffset() / 3600;

        return $timeDifference;
    }

    function getTransitionHour($timezone)
    {
        $dateTimeZone = new DateTimeZone($timezone);
        $transitionInfo = $dateTimeZone->getTransitions();

        // Iterate through transition rules to find the DST transition hour
        foreach ($transitionInfo as $transition) {
            // Check if the transition is DST and if the current time is before the transition
            if ($transition['isdst'] && Carbon::now($timezone)->lt(Carbon::parse($transition['time']))) {
                return Carbon::parse($transition['time'])->hour;
            }
        }

        // If no transition is found, return a default value (e.g., 19)
        return 19;
    }

    public function postNurses(Request $request)
    {
        $this->validate($request, [
            'case_id' => 'required|exists:patient_cases,id'
        ]);

        $case_id = $request->case_id;
        $users = User::with(['feedback' => function ($feedack) use ($case_id) {
            return $feedack->selectRaw('id,user_id,description,rating')->where('post_id', $case_id);
        }])->whereHas('shifts', function ($shifts) use ($case_id) {
            return $shifts->where('case_id', $case_id);
        })->with(['shifts' => function ($shifts) use ($case_id) {
            return $shifts->selectRaw("id,case_id,nurse_id,
            DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,
            TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
            TIME_FORMAT(
                DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                '%h:%i %p'
            ) AS end_time,
            CASE
            WHEN nurse_id IS NULL THEN ''
            ELSE 'booked'
        END AS status,is_sheet_filled,hours_worked")->where('case_id', $case_id);
        }])
            ->selectRaw('id,first_name,last_name,email,profile_image,rates,discipline,license_no,phone_number,(SELECT ROUND(AVG(rating), 1) from feedbacks where user_id = users.id) as avg_rating,is_approved,
            (CASE WHEN (select count(id) from shifts where case_id = "' . $case_id . '" AND nurse_id = users.id AND is_sheet_filled = 1) > 0 THEN 1 ELSE 0 END) as is_sheet_filled')
            ->get();
        // return $users;
        $users = $users->map(function ($users) {
            $data['id'] = $users->id;
            $data['first_name'] = $users->first_name;
            $data['last_name'] = $users->last_name;
            $data['email'] = $users->email;
            $data['profile_image'] = $users->profile_image;
            $data['rates'] = $users->rates;
            $data['discipline'] = $users->discipline;
            $data['license_no'] = $users->license_no;
            $data['phone_number'] = $users->phone_number;
            $data['is_sheet_filled'] = $users->is_sheet_filled;
            $data['avg_rating'] = $users->avg_rating;
            $data['is_approved'] = $users->is_approved;
            $data['feedback'] = $users->feedback;
            $data['shifts'] = $users->shifts->groupBy('date')->map(function ($shifts_by_date, $date) {
                return [
                    'date' => $date,
                    'shifts' => $shifts_by_date
                ];
            })->values();
            return $data;
        });

        return apiSuccessMessage("Nurses", $users);
    }

    public function posts(Request $request)
    {
        $this->validate($request, [
            'date' => 'required'
        ]);



        $formattedDate = DateTime::createFromFormat('m/d/Y',  $request->date)->format('Y-m-d');




        $cases = PatientCase::
            //     has('shifts')->with(['business_name:id,first_name,last_name,business_name',
            //      'shifts' => function ($shifts) {
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
            // }, 'shifts.nurse' => function ($nurse) {
            //     return $nurse->selectRaw('id,first_name,last_name,email,profile_image,rates,discipline,license_no,phone_number');
            // }])
            //     ->
            selectRaw("id, user_id,patient_name, location, dob, phone_number, gender, discipline, care_level, case_status,DATE_FORMAT(STR_TO_DATE(start_date, '%Y-%m-%d'), '%m/%d/%Y') AS start_date, DATE_FORMAT(STR_TO_DATE(end_date, '%Y-%m-%d'), '%m/%d/%Y') AS end_date, note,is_patient_died")
            ->selectRaw('CASE WHEN (SELECT COUNT(id) FROM shifts WHERE case_id = patient_cases.id AND nurse_id IS NOT NULL) > 0 THEN 0 ELSE 1 END AS can_edit')
            ->selectRaw('CASE WHEN STR_TO_DATE(end_date, "%Y-%m-%d") < "' . $formattedDate . '" THEN "Completed"
                        WHEN (SELECT COUNT(id) FROM shifts WHERE case_id = patient_cases.id AND nurse_id IS NULL) > 0 THEN "Available" ELSE "InProgress" END AS status,
                        (SELECT GROUP_CONCAT(id) FROM shifts WHERE case_id = patient_cases.id AND nurse_id IS NULL) AS shift_ids ,
                        CASE WHEN(
                        (SELECT COUNT(id) FROM shifts WHERE case_id = patient_cases.id AND nurse_id IS NULL) =
                        (SELECT COUNT(DISTINCT shift_requests.shift_id) FROM shift_requests WHERE FIND_IN_SET(shift_requests.shift_id,shift_ids))
                        )
                        THEN "true"
                        ELSE "false" END
                        AS requested,
                        CASE WHEN care_level != "continuous care" THEN (select TIME_FORMAT(STR_TO_DATE(start_time, "%H:%i:%s"), "%h:%i %p") from shifts where case_id = patient_cases.id limit 1) ELSE "" END as start_time,
                        CASE WHEN care_level != "continuous care" THEN (select TIME_FORMAT(
                                                                        DATE_ADD(STR_TO_DATE(end_time, "%H:%i:%s"), INTERVAL 1 MINUTE),
                                                                        "%h:%i %p"
                                                                    ) from shifts where case_id = patient_cases.id limit 1) ELSE "" END as end_time
                        ')
            ->whereUserId(auth()->id())
            ->latest('id')
            ->get();
        // return $cases;
        // $cases = $this->assignmentController->formatCaseAndShiftsData($cases);

        return apiSuccessMessage("Posts", $cases);
    }

    public function editPost(Request $request)
    {
        $this->validate($request, [
            "case_id" => "required|exists:patient_cases,id",
            "patient_name" => "required",
            "location" => "required",
            "dob" => "required",
            "phone_number" => "required",
            "gender" => "required|in:male,female",
            "discipline" => "required|in:RN,LVN,HHA",
            "care_level" => "required|in:routine care,continuous care,inpatient care,respite care",
            "start_date" => "required",
            "end_date" => "nullable",
            "case_status" => "required|in:PRN,Ongoing",
            "start_time" => "nullable",
            "end_time" => "nullable",
            "note" => "nullable"
        ]);

        try {
            DB::beginTransaction();
            $case_id = $request->case_id;
            $shifts = Shifts::whereCaseId($case_id);

            $shiftsClone = clone $shifts;

            $checkIfShiftsAreBooked = $shiftsClone->whereNotNull('nurse_id')->exists();

            if ($checkIfShiftsAreBooked) {
                return commonErrorMessage("Can not edit this post", 400);
            }


            $care_level = $request->care_level;
            $start_date = $request->start_date;
            $start_date = Carbon::createFromFormat('d/m/Y', $start_date);
            $end_date = "";

            if ($care_level == "continuous care") {
                $end_date = Carbon::createFromFormat('d/m/Y', $request->end_date);
            } else {
                $end_date = $start_date;
            }

            $patient = PatientCase::whereId($case_id)->first();
            $patient->update([
                'user_id' => auth()->id(),
                'patient_name' => $request->patient_name,
                'location' => $request->location,
                'dob' => $request->dob,
                'phone_number' => $request->phone_number,
                'gender' => $request->gender,
                'discipline' => $request->discipline,
                'care_level' => $care_level,
                'case_status' => $request->case_status,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'note' => $request->note,
            ]);

            // return $patient;

            $shift_ids = $shifts->pluck('id');
            $this->deleteNotificationAndShiftRequests($shift_ids);
            $shifts->delete();

            $case_id = $patient->id;
            if ($care_level == "continuous care") {
                $this->assignmentController->createContinousCareShifts($start_date, $end_date, $case_id);
            } else {
                Shifts::create([
                    'case_id' => $case_id,
                    'date' =>   $start_date,
                    'start_time' => $request->start_time,
                    'end_time' => Carbon::createFromFormat('H:i:s', $request->end_time)->subMinute()->format('H:i:s'),
                    'status' => 'available',
                ]);
            }

            DB::commit();

            return commonSuccessMessage("Post update successfully.");
        } catch (\Throwable $th) {
            DB::rollback(); // Rollback the transaction in case of an exception
            return $th;
        }
    }


    public function postShifts(Request $request)
    {
        $this->validate($request, [
            'nurse_id' => 'required',
            'case_id' => 'required',
        ]);
        $nurse_id = $request->nurse_id;

        $shifts = Shifts::whereCaseId($request->case_id)->selectRaw("id,DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,
                                TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
                                TIME_FORMAT(
                                    DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                                    '%h:%i %p'
                                ) AS end_time,
                                CASE
                                WHEN nurse_id IS NOT NULL THEN 'booked'
                                WHEN (select count(id) from shift_requests where shift_id = shifts.id AND sent_by_nurse = 0 AND nurse_id = '" . $nurse_id . "' AND status = 'pending') = 1
                                THEN 'request_sent'
                                WHEN (select count(id) from shift_requests where shift_id = shifts.id AND status = 'pending' AND nurse_id = '" . $nurse_id . "') > 0 THEN 'requested'
                                ELSE ''
                                END as status")
            ->get();

        $shifts = $shifts->groupBy('date')->map(function ($shifts_by_date, $date) {
            return [
                'date' => $date,
                'shifts' => $shifts_by_date
            ];
        })->values();
        return apiSuccessMessage("Success", $shifts);
    }

    public function cancelCase(Request $request)
    {
        $this->validate($request, [
            'case_id' => 'required|exists:patient_cases,id',
            'date' => 'required',
            'time' => 'required',
            'cancel_reason' => 'required'
        ]);


        $date = DateTime::createFromFormat('m/d/Y', $request->date)->format('Y-m-d');
        $time = $request->time;
        $case_id = $request->case_id;
        $reason = $request->cancel_reason;

        // Tracking the shift which has been cancelled during the shift
        Shifts::whereCaseId($case_id)->where('date', $date)
            ->whereNurseId(auth()->id())
            ->whereRaw("(start_time <= end_time and '$time' >= start_time and '$time' <= end_time) or
            (end_time < start_time and ('$time' <= end_time or '$time' >= start_time))")
            ->update(['is_cancelled' => 1, 'cancelled_at' =>  $date . " " . $time]);

        // Cancelling all future shifts
        $shifts = Shifts::when($reason !== 'patient_died', function ($q) {
            $q->whereNurseId(auth()->id());
        })
            ->whereCaseId($case_id)
            ->where(function ($query) use ($date, $time) {
                $query->where('date', $date)
                    ->whereRaw("(start_time) >= ?", [$time])
                    ->Orwhere('date', '>', $date);
            })
            ->whereStatus('booked');;
        // return $shifts->get();

        if ($reason == 'patient_died') {
            PatientCase::whereIn('id', $this->getAllParentCasesIds($case_id))->update(['is_patient_died' => 1]);
        }
        $shift_ids = $shifts->pluck('id');

        $cancel_reason = CancelReason::create(['case_id' => $case_id, 'nurse_id' => auth()->id(), 'reason' => $reason]);
        foreach ($shift_ids as $shift_id) {
            CancelledShift::create(['nurse_id' => auth()->id(), 'shift_id' => $shift_id, 'reason_id' => $cancel_reason->id]);
        }
        $shifts->update(['status' => 'available', 'nurse_id' => null]);
        ShiftRequest::whereIn('shift_id', $shift_ids)->delete();
        return commonSuccessMessage("Case cancelled");
    }

    public function getAllParentCasesIds($case_id)
    {
        return collect(DB::select("
        WITH RECURSIVE CategoryHierarchy AS (
            SELECT id,patient_case_id
            FROM patient_cases
            WHERE id = :caseId

            UNION ALL

            SELECT c.id, c.patient_case_id
            FROM patient_cases c
            JOIN CategoryHierarchy ch ON ch.patient_case_id = c.id
        )

        SELECT id FROM CategoryHierarchy
    ", ['caseId' => $case_id]))->pluck('id');
    }
    public function reOpenCase(Request $request, AssignmentController $assignmentController)
    {
        $this->validate($request, [
            'case_id' => 'required|exists:patient_cases,id',
            'start_date' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $case = PatientCase::whereId($request->case_id)->first();

            $care_level = $case->care_level;
            $start_date = $request->start_date;
            $start_date = Carbon::createFromFormat('d/m/Y', $start_date);
            $end_date = "";

            if ($care_level == "continuous care") {
                // $this->validate($request, ['end_date' => 'required']);

                $end_date = Carbon::createFromFormat('d/m/Y', $request->end_date);
            } else {
                $end_date = $start_date;
            }


            $patient = PatientCase::create([
                'user_id' => $case->user_id,
                'patient_name' => $case->patient_name,
                'location' => $case->location,
                'dob' => $case->dob,
                'phone_number' => $case->phone_number,
                'gender' => $case->gender,
                'discipline' => $case->discipline,
                'care_level' => $care_level,
                'case_status' => $case->case_status,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'note' => $case->note,
                'patient_case_id' => $case->id,
            ]);

            $case_id = $patient->id;
            if ($care_level == "continuous care") {
                $assignmentController->createContinousCareShifts($start_date, $end_date, $case_id);
            } else {
                Shifts::create([
                    'case_id' => $case_id,
                    'date' =>   $start_date,
                    'start_time' => $request->start_time,
                    'end_time' => Carbon::createFromFormat('H:i:s', $request->end_time)->subMinute()->format('H:i:s'),
                    'status' => 'available',
                ]);
            }
            // return $case;
            DB::commit();

            return commonSuccessMessage("Case Re Opened.");
        } catch (\Throwable $th) {
            DB::rollback(); // Rollback the transaction in case of an exception
            return $th;
        }
    }

    public function deletePost(Request $request)
    {
        $this->validate($request, [
            "case_id" => "required|exists:patient_cases,id",
        ]);

        try {
            DB::beginTransaction();
            $case_id = $request->case_id;
            $shifts = Shifts::whereCaseId($case_id);

            $shiftsClone = clone $shifts;

            $checkIfShiftsAreBooked = $shiftsClone->whereNotNull('nurse_id')->exists();

            if ($checkIfShiftsAreBooked) {
                return commonErrorMessage("Can not delete this post", 400);
            }

            $shift_ids = $shifts->pluck('id');
            $this->deleteNotificationAndShiftRequests($shift_ids);
            $shifts->delete();

            DB::commit();

            return commonSuccessMessage("Post deleted successfully.");
        } catch (\Throwable $th) {
            DB::rollback(); // Rollback the transaction in case of an exception
            return $th;
        }
    }

    public  function deleteNotificationAndShiftRequests($shift_ids)
    {
        try {
            DB::beginTransaction();

            ShiftRequest::whereIn('shift_id', $shift_ids)->delete();
            Notification::whereIn('shift_id', $shift_ids)->delete();

            DB::commit();

            return true;
        } catch (\Throwable $th) {
            DB::rollback(); // Rollback the transaction in case of an exception
            return $th;
        }
    }
}
