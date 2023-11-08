<?php

namespace App\Http\Controllers\API;


use App\Http\Controllers\Controller;
use App\Models\CaseSignature;
use App\Models\Notification;
use App\Models\PatientCase;
use App\Models\ShiftRequest;
use App\Models\Shifts;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    public function createRouteSheet(Request $request)
    {
        $this->validate($request, [
            'case_id' => 'required|exists:patient_cases,id',
            'signature' => 'required',
            'service_code' => 'required',
            'total_amount' => 'required',
            'data.*' => 'required|array',
            'data.*.shift_id' => 'required|exists:shifts,id',
            'data.*.hours_worked' => 'required',
            // 'data.*.hourly_rate' => 'required',
            // 'data.*.total_amount' => 'required',
        ]);

        // return $request->all();
        CaseSignature::updateOrCreate(
            ['nurse_id' => auth()->id(), 'case_id' => $request->case_id],
            ['signature' => $request->signature, 'service_code' => $request->service_code, 'total_amount' => $request->total_amount]
        );
        foreach ($request->data as $data) {
            Shifts::whereId($data['shift_id'])->whereIsSheetFilled(false)->whereNurseId(auth()->id())->update(
                [
                    'status' => 'completed',
                    'is_sheet_filled' => '1',
                    'hours_worked' => $data['hours_worked'],
                    // 'hourly_rate' => $data['hourly_rate'],
                    // 'total_amount' => $data['total_amount']
                ]
            );

            ShiftRequest::whereNurseId(auth()->id())->whereShiftId($data['shift_id'])->whereStatus('accepted')->update(['status' => 'completed']);
        }

        return commonSuccessMessage("Success");
    }
    public function createAssingment(Request $request)
    {
        $this->validate($request, [
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


            $care_level = $request->care_level;
            $start_date = $request->start_date;
            $start_date = Carbon::createFromFormat('d/m/Y', $start_date);
            $end_date = "";

            if ($care_level == "continuous care") {
                $end_date = Carbon::createFromFormat('d/m/Y', $request->end_date);
            } else {
                $end_date = $start_date;
            }

            $patient = PatientCase::create([
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

            $case_id = $patient->id;
            if ($care_level == "continuous care") {
                $this->createContinousCareShifts($start_date, $end_date, $case_id);
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

            return response()->json([
                "status" => 1,
                "message" => "Assigment Created",
            ]);
        } catch (\Throwable $th) {
            DB::rollback(); // Rollback the transaction in case of an exception
            return $th;
        }
    }

    public function availableCases(Request $request)
    {
        $this->validate($request, [
            'date' => 'required'
        ]);
        $nurse_id =  auth()->id();
        $discipline =  auth()->user()->discipline;
        // return $discipline;
        $date = DateTime::createFromFormat('m/d/Y', $request->date)->format('Y-m-d');
        // return $date;
        $cases = PatientCase::has('shifts')->with(['business_name:id,first_name,last_name,business_name', 'shifts' => function ($shifts) use ($nurse_id) {
            return $shifts->selectRaw("id,case_id,
                                            DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,
                                            TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
                                            TIME_FORMAT(
                                                DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                                                '%h:%i %p'
                                            ) AS end_time,
                                            CASE
                                            WHEN nurse_id IS NOT NULL AND nurse_id = $nurse_id THEN 'booked_by_you'
                                            WHEN nurse_id IS NOT NULL AND nurse_id != $nurse_id THEN 'booked_by_other'
                                            WHEN (SELECT sent_by_nurse from shift_requests where nurse_id = $nurse_id and shift_requests.shift_id = shifts.id order by id desc LIMIT 1) = 1 THEN 'applied'
                                            WHEN (SELECT sent_by_nurse from shift_requests where nurse_id = $nurse_id and shift_requests.shift_id = shifts.id order by id desc LIMIT 1 ) = 0 THEN 'reqeustd'
                                            WHEN nurse_id IS NULL THEN ''
                                        END AS status");
        }])
            ->selectRaw("*,(SELECT count(id) from shifts where nurse_id IS NULL AND shifts.case_id = patient_cases.id) as available_shifts_count,
                (CASE WHEN ( SELECT COUNT(case_id) from shift_requests where nurse_id = $nurse_id and status = 'accepted' ) = 0 THEN 'TRUE'
                    WHEN (SELECT COUNT(case_id) from shift_requests where nurse_id = $nurse_id and status = 'accepted' AND case_id = patient_cases.id ) > 0 THEN 'TRUE'
                    ELSE 'FALSE'
                END
                ) as can_apply
            ")
            ->where(function ($query) use ($date, $discipline) {
                $query
                    ->whereDate('end_date', '>=', $date)
                    ->where('discipline', $discipline);
            })
            ->whereIsPatientDied(false)
            ->having('available_shifts_count', '>', 0)
            ->latest()
            ->get();
        // return $cases;
        $cases = $this->formatCaseAndShiftsData($cases);
        return response()->json([
            "status" => 1,
            "message" => "cases!",
            "data" => $cases
        ]);
    }

    public function inProgressCases(Request $request)
    {
        $this->validate($request, [
            'date' => 'required',
            'time' => 'required',
        ]);
        $nurse_id =  auth()->id();
        $date = $request->date;
        $time = $request->time;
        $date = Carbon::createFromFormat('m/d/Y', $date)->format('Y-m-d');

        // return $date;

        $cases = PatientCase::has('booked_shifts')->with(['business_name:id,first_name,last_name,business_name', 'shifts' => function ($shifts) use ($nurse_id, $date, $time) {
            return $shifts->selectRaw("id,case_id,
                                            DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,
                                            TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
                                            TIME_FORMAT(
                                                DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                                                '%h:%i %p'
                                            ) AS end_time
                                            ,
                                            is_cancelled,
                                            CASE
                                            WHEN nurse_id IS NOT NULL AND nurse_id = $nurse_id THEN 'booked_by_you'
                                            WHEN nurse_id IS NOT NULL AND nurse_id != $nurse_id THEN 'booked_by_other'
                                            WHEN (SELECT count(id) from shift_requests where nurse_id = $nurse_id and status = 'pending' AND shift_requests.shift_id = shifts.id) > 0 THEN 'applied'
                                            WHEN nurse_id IS NULL THEN ''
                                        END AS status, (SELECT SUBSTRING_INDEX(GROUP_CONCAT(is_cancelled), ',', -1) AS last_lesson FROM shifts WHERE nurse_id = $nurse_id order by id) as is_last_cancelled")
                ->where('nurse_id', $nurse_id)
                ->whereStatus('booked')
                ->where(function ($date_filter) use ($date, $time) {
                    $date_filter->where('date', '=', $date)
                        ->whereRaw("(end_time) >= ?", [$time])
                        ->Orwhere('date', '>', $date);
                })
                ->having('is_last_cancelled', 0);
        }])
            ->latest()
            ->get();
        // return $cases;
        $cases = $cases->filter(function ($case) {
            return count($case['shifts']) > 0;
        });
        $cases = $this->formatCaseAndShiftsData($cases);

        return response()->json([
            "status" => 1,
            "message" => "cases!",
            "data" => collect($cases)->values()
        ]);
    }

    public function  completedCases(Request $request)
    {
        $this->validate($request, [
            'date' => 'required',
            'time' => 'required',
        ]);


        $nurse_id =  auth()->id();
        $date = $request->date;
        $time = $request->time;
        $date = Carbon::createFromFormat('m/d/Y', $date)->format('Y-m-d');

        // return $nurse_id;
        // return $date;

        $cases = PatientCase::with(['business_name:id,first_name,last_name,business_name', 'case_signature:id,case_id,signature,service_code,hourly_rate,total_amount', 'shifts' => function ($shifts) use ($nurse_id, $date, $time) {
            return $shifts->selectRaw("id,case_id,
                                            DATE_FORMAT(STR_TO_DATE(date, '%Y-%m-%d'), '%m/%d/%Y') AS date,
                                            TIME_FORMAT(STR_TO_DATE(start_time, '%H:%i:%s'), '%h:%i %p') AS start_time,
                                            TIME_FORMAT(
                                                DATE_ADD(STR_TO_DATE(end_time, '%H:%i:%s'), INTERVAL 1 MINUTE),
                                                '%h:%i %p'
                                            ) AS end_time,is_sheet_filled,hours_worked,is_cancelled
                                            ")
                ->where('nurse_id', $nurse_id)
                ->whereIn('status', ['booked', 'completed'])
                ->where(function ($date_filter) use ($date, $time) {
                    $date_filter->where('date', '=', $date)
                        ->whereRaw("(end_time) <= ?", [$time])
                        ->Orwhere('date', '<', $date)
                        ->orWhere('is_cancelled', '1');
                });
        }])
            ->selectRaw(
                "*, (SELECT count(id)  from shifts where  nurse_id = '" . $nurse_id . "' AND shifts.case_id = patient_cases.id AND  status IN ('booked', 'completed')  AND
                                    ( ((date = '" . $date . "' AND end_time <= '" . $time . "') OR is_cancelled = 1) OR date < '" . $date . "' )
                             )  as shifts_count,
                             (
                                CASE
                                WHEN (SELECT SUBSTRING_INDEX(GROUP_CONCAT(is_cancelled), ',', -1) AS last_lesson FROM shifts WHERE nurse_id = $nurse_id AND shifts.case_id = patient_cases.id order by id) = 1 THEN 1
                                ELSE CASE WHEN
                                    (
                                        SELECT COUNT(id)
                                        FROM shifts
                                        WHERE nurse_id = '" . $nurse_id . "'
                                        AND shifts.case_id = patient_cases.id
                                        AND status = 'booked'
                                        AND (
                                            (date = '" . $date . "' AND end_time >= '" . $time . "')
                                            OR date > '" . $date . "'
                                        )
                                    ) > 0
                                    THEN 0
                                    ELSE 1
                                END
                                END
                            ) AS is_case_completed,
                            (
                                CASE WHEN (SELECT count(id)  from shifts where  nurse_id = '" . $nurse_id . "' AND shifts.case_id = patient_cases.id
                                AND  status = 'booked'


                                   ) > 0 THEN 0 ELSE 1 END

                                )  as all_route_sheets_filled"
            )
            ->having('shifts_count', '>', 0)
            ->latest()
            ->get();
        // return $cases;
        $cases = $this->formatCaseAndShiftsData($cases);

        return response()->json([
            "status" => 1,
            "message" => "cases!",
            "data" => $cases
        ]);
    }

    public function caseDetail(Request $request)
    {
        $this->validate($request, [
            'case_id' => 'required|exists:patient_cases,id',
            'nurse_id' => 'required'
        ]);

        $nurse_id = $request->nurse_id;
        $role = auth()->user()->role;
        $case = PatientCase::with(['business_name:id,first_name,last_name,business_name', 'case_signature' => function ($signature) use ($nurse_id) {
            return $signature->selectRaw('id,case_id,signature,service_code,hourly_rate,total_amount')->where('nurse_id', $nurse_id);
        }, 'shifts' => function ($shifts) use ($nurse_id) {
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
            ->selectRaw(
                "id,patient_name,location,dob,phone_number,gender,discipline,care_level,case_status,DATE_FORMAT(STR_TO_DATE(start_date, '%Y-%m-%d'), '%m/%d/%Y') AS start_date,DATE_FORMAT(STR_TO_DATE(end_date, '%Y-%m-%d'), '%m/%d/%Y') AS end_date,note,is_patient_died,user_id"
            )
            // ->having('shifts_count', '>', 0)
            ->whereId($request->case_id)
            ->first();

        $groupedShifts = $case->shifts->groupBy('date')->map(function ($shiftsByDate, $date) {
            return [
                'date' => $date,
                'shifts' => $shiftsByDate
            ];
        })->values();
        unset($case->shifts);
        // $case->shifts = null; // Update the shifts property with grouped shifts

        // return $case;
        return apiSuccessMessage("Case Detail", ['case' => $case, 'shifts' => $groupedShifts]);
    }

    public function sendShiftRequest(Request $request, NurseController $nurseController)
    {
        $this->validate($request, [
            'case_id' => 'required|exists:patient_cases,id',
            'shift_ids' => 'required|exists:shifts,id',
            'nurse_id' => 'required|exists:users,id',
        ]);
        $shift_ids = $request->shift_ids;
        $case_id = $request->case_id;
        $nurse_id = $request->nurse_id;
        //Checking if the nurse has already a case which is in progress
        $checkUserHasOtherCaseShiftInAssigned = ShiftRequest::where('nurse_id', $nurse_id)
            ->where('case_id', '!=', $case_id)
            ->whereStatus('accepted')
            ->exists();
        if ($checkUserHasOtherCaseShiftInAssigned) {
            return commonErrorMessage("An other case is in progress.", 400);
        }

        $shifts = Shifts::where('case_id', $case_id)->whereNull('nurse_id')->pluck('id');
        // Filtering Shifts if they are already assinged to other nurse
        $filtered_shift_ids = $shifts->filter(function ($ids) use ($shift_ids) {
            return in_array($ids, $shift_ids);
        })->intersect($shift_ids)->values();
        $case = PatientCase::select('user_id')->whereId($case_id)->first();
        foreach ($filtered_shift_ids as $shift_id) {
            $data = [
                'nurse_id' => $nurse_id,
                'hospice_id' => $case->user_id,
                'case_id' => $case_id,
                'shift_id' => $shift_id,
            ];
            $hasAlreadyReqeust = ShiftRequest::where($data)->whereIn('status', ['completed' => 'pending', 'accepted'])->exists();

            if (!$hasAlreadyReqeust) {
                $data['status'] = "pending";
                $role = auth()->user()->role;
                $data['sent_by_nurse'] = $role == "hospice" ? 0 : 1;
                $reqeust = ShiftRequest::create($data);
                $receiver_id = $role == "hospice" ? $nurse_id : $case->user_id;
                $name = $role == "hospice" ? auth()->user()->business_name : auth()->user()->first_name;
                $message = $name . " has send you a case request.";

                $nurseController->add_notification(auth()->id(), $receiver_id, $reqeust->id, $shift_id, $reqeust->id, "Case Request", $message, $nurse_id, $case_id);
            }
        }

        return commonSuccessMessage("Request sent successfully");
    }

    public function acceptOrRejectShiftRequest(Request $reqeust)
    {
        $this->validate($reqeust, [
            'type' => 'required|in:accept,reject',
            'request_id' => 'required|exists:shift_requests,id',
            'notification_id' => 'required|exists:notifications,id',
        ], [
            'request_id.exists' => 'The selected case is already occupied.',
            'notification_id.exists' => 'The selected case is already occupied.',
        ]);

        try {
            //code...
            DB::beginTransaction();

            $type = $reqeust->type;

            $notification_id = $reqeust->notification_id;
            $reqeust = ShiftRequest::whereId($reqeust->request_id)->first();

            $shift_id = $reqeust->shift_id;
            $reqeust_id = $reqeust->id;
            $nurse_id = $reqeust->nurse_id;
            $case_id = $reqeust->case_id;

            $notification = Notification::whereId($notification_id);

            if ($type == "accept") {
                if ($reqeust->status == "accepted") {
                    return commonErrorMessage("Request already accept", 400);
                }

                if ($reqeust->status == "rejected") {
                    return commonErrorMessage("Can not accept request after rejecting", 400);
                }

                if ($reqeust->status == "completed") {
                    return commonErrorMessage("Request already completed", 400);
                }

                $nurseAlreadyShiftsCount = ShiftRequest::whereCaseId($case_id)->whereNurseId($nurse_id)->whereIn('status', ['accepted', 'completed'])->count();
                if ($nurseAlreadyShiftsCount == 0) {
                    $rate = User::whereId($nurse_id)->first()?->rates;
                    CaseSignature::create(

                        ['nurse_id' => $nurse_id, 'case_id' => $case_id, 'hourly_rate' =>  str_replace('$', '', $rate)]
                        // ['signature' => $request->signature, 'service_code' => $request->service_code]
                    );
                }
                $reqeust->status = "accepted";
                $reqeust->save();

                Shifts::whereId($shift_id)->update(['nurse_id' => $nurse_id, 'status' => 'booked']);


                ShiftRequest::where(function ($query) use ($shift_id, $reqeust_id, $nurse_id, $case_id) {
                    $query->where('shift_id', $shift_id)
                        ->where('id', '!=', $reqeust_id)
                        ->orWhere('nurse_id', $nurse_id)
                        ->where('case_id', '!=', $case_id);
                })
                    ->where('status', 'pending')
                    ->delete();
                $notification->delete();

                // remove all current shifts of other nurses and remove other cases shifts of current nurse whose case is being accepted
                Notification::where('shift_id', $shift_id)->orWhere('nurse_id', $nurse_id)->where('case_id', '!=', $case_id)->delete();

                DB::commit();
                return commonSuccessMessage("Request accepted succesfully.");
            }

            $reqeust->delete();
            $notification->delete();

            DB::commit();
            return commonSuccessMessage("Request rejected succesfully.");
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollback(); // Rollback the transaction in case of an exception
            return $th;
        }
    }

    public function createContinousCareShifts($start_date, $end_date, $case_id)
    {
        // Set the start and end dates
        // $start_date = Carbon::createFromFormat('d/m/Y',$start_date);
        // $end_date = Carbon::createFromFormat('d/m/Y', $end_date);

        // Iterate through the dates
        while ($start_date <= $end_date) {

            $first_shift_start = $start_date->copy()->setTime(0, 0, 0);
            $first_shift_end = $first_shift_start->copy()->setTime(11, 59, 0);

            Shifts::create([
                'case_id' => $case_id,
                'date' => $start_date,
                'start_time' => $first_shift_start,
                'end_time' => $first_shift_end,
                'status' => 'available',
            ]);


            $second_shift_start = $first_shift_end->copy()->setTime(12, 0, 0);
            $second_shift_end = $first_shift_end->copy()->addDay()->setTime(23, 59, 0);
            // return [$first_shift_start, $first_shift_end, $second_shift_start, $second_shift_end];
            Shifts::create([
                'case_id' => $case_id,
                'date' => $start_date,
                'start_time' => $second_shift_start,
                'end_time' => $second_shift_end,
                'status' => 'available',
            ]);

            // Move to the next day
            $start_date->addDay();
        }

        return true;
    }

    public function formatCaseAndShiftsData($shifts)
    {
        return  $shifts->map(function ($shifts) {
            $data['id'] = $shifts->id;
            $data['patient_name'] = $shifts->patient_name;
            $data['location'] = $shifts->location;
            $data['dob'] = $shifts->dob;
            $data['phone_number'] = $shifts->phone_number;
            $data['gender'] = $shifts->gender;
            $data['discipline'] = $shifts->discipline;
            $data['care_level'] =  $shifts->care_level;
            $data['case_status'] = $shifts->case_status;
            $data['start_date'] = Carbon::createFromFormat('Y-m-d', $shifts->start_date)->format('m/d/Y');
            $data['end_date'] = Carbon::createFromFormat('Y-m-d', $shifts->end_date)->format('m/d/Y');
            $data['note'] = $shifts->note;

            if (isset($shifts->can_apply)) {
                $data['can_apply'] = $shifts->can_apply;
            }

            if (isset($shifts->is_patient_died)) {
                $data['is_patient_died'] = $shifts->is_patient_died;
            }
            if (isset($shifts->is_case_completed)) {
                $data['is_case_completed'] = $shifts->is_case_completed;
            }
            if (isset($shifts->all_route_sheets_filled)) {
                $data['all_route_sheets_filled'] = $shifts->all_route_sheets_filled;
                $data['case_signature'] = $shifts->case_signature;
            }

            if (isset($shifts->past)) {
                $data['case_signature'] = $shifts->case_signature;
                $data['avg_rating'] = $shifts->avg_rating;
                $data['feedback'] = $shifts->feedback;
            }

            if (isset($shifts->requested)) {
                $data['requested'] = $shifts->requested;
            }

            if (isset($shifts->can_edit)) {
                $data['can_edit'] = $shifts->can_edit;
                $data['status'] = $shifts->status;
            }

            if (isset($shifts->business_name)) {
                $data['business_name'] = $shifts->business_name;
            }

            $data['shifts'] = $shifts->shifts->groupBy('date')->map(function ($shifts_by_date, $date) {
                return [
                    'date' => $date,
                    'shifts' => $shifts_by_date
                ];
            })->values();
            return $data;
        });
    }
}
