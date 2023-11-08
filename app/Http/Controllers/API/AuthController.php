<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponser;
use App\Imports\ImportAttendance;
use App\Models\HelpAndSupport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AuthController extends Controller
{
    use ApiResponser;


    /** Register user */

    public function upload(Request $request)
    {
        Excel::import(
            new ImportAttendance,
            $request->file('file')->store('files')
        );
        return
            ['sucess'];
    }

    public function signup(Request $request)
    {
        $customMessages = [
            'email.email' => 'Please enter valid email address.',
            'required' => ":attribute can't be empty",
            'password.regex' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.min' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.max' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
        ];

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'business_name' => 'nullable',
            'role' => 'required',
            'email' => 'required|unique:users|email|max:255|confirmed',
            'email_confirmation' => 'required|max:255',
            'phone_number' => 'numeric',
            'address' => 'nullable',
            'password' => 'required|min:8|max:255|confirmed|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'password_confirmation' => 'required|min:8',
            'profile_image' => 'nullable',
            'device_type' => "required",
            "device_token" =>  "required"
        ], $customMessages);

        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $otp = 123456; //random_int(100000, 999999);

        $user = new User;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->business_name = $request->business_name;
        $user->role = $request->role;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->address = $request->address;
        $user->password = Hash::make($request->password);
        $user->otp = $otp;
        $user->device_type = $request->device_type;
        $user->device_token = $request->device_token;

        if ($request->hasFile('profile_image')) {
            $imageName = time() . '.' . $request->profile_image->getClientOriginalExtension();
            $request->profile_image->move(public_path('/uploadedimages'), $imageName);
            $file_path = asset('uploadedimages') . "/" . $imageName;

            $user->profile_image = $file_path;
        }

        if ($user->save()) {

            $details = [
                'subject' => 'Verify your email',
                'email' => $request->email,
                'otp' => $otp,
                'view' => 'emails.verify-email'
            ];

            // Mail::to($details['email'])->send(new \App\Mail\SendEmail($details));

            $data = [
                'email' => $request->email,
                'password' => $request->password
            ];

            auth()->attempt($data);

            // $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 1,
                'message' => "OTP verification code has been sent to your email address.",
                // 'token' => $token,
                'data' => [
                    'id' => $user->id
                ]

            ], 200);
        } else {
            return $this->error('Sign up not processed', 400);
        }
    }


    /** OTP verify */

    public function verification(Request $request)
    {
        $controls = $request->all();
        $rules = array(
            "otp" => "required|min:6|numeric",
            "user_id" => "required|exists:users,id",
            'type' => 'required|in:forgot,verification'
        );
        $customMessages = [
            'required' => 'The :attribute is required.',
            'numeric' => 'The :attribute must be numeric',
            'exists' => 'The :attribute does not exist',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }
        $user = User::where([['id', '=', $request->user_id], ['otp', '=', $request->otp]])->first();

        if ($user) {
            if ($request->type == "forgot") {
                User::where('id', $request->id)->update(['device_token' => $request->device_token, 'is_forgot' => "1", "otp" => null]);
                $check_otp = DB::table("password_resets")
                    ->where(["token" => $request->otp, "email" => $request->email])
                    ->first();

                if ($check_otp) {
                    $totalDuration = Carbon::parse($check_otp->created_at)->diffInHours(
                        Carbon::now()
                    );
                    if ($totalDuration > 1) {
                        return response()->json([
                            "status" => 0,
                            "message" => "OTP expired",
                        ]);
                    }
                    return response()->json([
                        "status" => 1,
                        "message" => "OTP verified successfully",
                    ]);
                }
            } elseif ($request->type == "verification") {
                User::where('id', $request->id)->update(['device_token' => $request->device_token, "otp" => null]);
            }

            Auth::loginUsingId($user->id, true);
            $token = $user->createToken('authToken')->plainTextToken;
            $user->email_verified_at = Carbon::now();
            $user->account_verified = 1;
            $user->save();
            $user = User::selectRaw('*,(SELECT ROUND(AVG(rating), 1) from feedbacks where user_id = users.id) as avg_rating')->whereId($user->id)->first();
            $user["user_id"] = $user->id;

            return response()->json([
                'status' => 1,
                'message' => 'Account verified successfully',
                'token' => $token,
                'data' => $user,

            ], 200);
        } else {
            return response()->json([
                'status' => 0,
                'message' => 'Invalid OTP verification code'
            ], 400);
        }
    }


    /** Resend code */

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id'
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $user = User::where(['id' => $request->user_id])->first();

        if (!empty($user)) {
            $otp = 123456; //random_int(100000, 999999);

            User::whereId($user->id)->update(['otp' => $otp]);

            $details = [
                'subject' => 'Verify your email',
                'email' => $request->email,
                'otp' => $otp,
                'view' => 'emails.verify-email'
            ];

            // Mail::to($details['email'])->send(new \App\Mail\SendEmail($details));

            return $this->success('We have resend  OTP verification code at your email address');
        } else {
            return $this->error('User not found.', 404);
        }
    }


    /** Login */

    public function login(Request $request)
    {

        $customMessages = [
            'email.email' => 'Please enter valid email address.',
            'required' => ":attribute can't be empty",
            'exists' => 'Invalid Email Address',
            'password.regex' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.min' => 'The password must be at least 8 characters and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.max' => 'The password must be at least 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
        ];

        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email|max:255',
            'password' => 'required|min:8|max:255',
            'role' => 'required',
            'device_type' => 'required',
            'device_token' => 'required'
        ], $customMessages);


        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $data = [
            'email' => $request->email,
            'password' => $request->password,
            'role' => $request->role
        ];

        $user = User::where('email', $request->email)->first();

        if (!empty($user)) {

            $user_blocked = $user->is_blocked;

            if ($user_blocked == 0) {
                $user_approved = $user->is_approved;
                if ($user_approved == 1) {
                    if (Hash::check($request->password, $user->password)) {
                        if (auth()->attempt($data)) {

                            if (auth()->user()->account_verified == 0) {

                                $otp = 123456; //random_int(100000, 999999);

                                User::whereId($user->id)->update(['otp' => $otp]);

                                $details = [
                                    'subject' => 'Verify your email',
                                    'email' => $request->email,
                                    'otp' => $otp,
                                    'view' => 'emails.verify-email'
                                ];

                                // Mail::to($details['email'])->send(new \App\Mail\SendEmail($details));

                                return response()->json([
                                    'status' => 1,
                                    'message' => 'Please verify your account, OTP successfully sent to your email address',
                                    'data' => auth()->user(),
                                ], 200);
                            } else {

                                User::whereId(auth()->user()->id)->update(['device_type' => $request->device_type, 'device_token' => $request->device_token]);
                                $user->tokens()->delete();
                                $token = $user->createToken('LaravelAuthToken')->plainTextToken;
                                $user->device_type = $request->device_type;
                                $user->device_token = $request->device_token;
                                $user->api_token = $token;
                                $user->save();
                                $user = User::selectRaw('*,(SELECT ROUND(AVG(rating), 1) from feedbacks where user_id = users.id) as avg_rating')->whereId($user->id)->first();
                                $user["user_id"] = $user->id;

                                return response()->json([
                                    'status' => 1,
                                    'message' => 'User successfully logged in',
                                    'token' => $token,
                                    'data' => $user
                                ], 200);
                            }
                        } else {
                            return $this->error('Invalid credentials', 401);
                        }
                    } else {
                        return $this->error('Password is incorrect', 400);
                    }
                } else {
                    return $this->error('User is not approved by the admin', 400);
                }
            } else {
                return $this->error('User is blocked', 400);
            }
        } else {
            return $this->error('Email is incorrect', 400);
        }
    }


    /** Forgot password */

    public function forgotPassword(Request $request)
    {

        $controls = $request->all();
        $rules = array(
            'email' => 'required|email|exists:users,email'
        );
        $customMessages = [
            'required' => 'The :attribute is required.',
            'exists' => 'The :attribute does not exist',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        } else {
            $user = User::where('email', $request->email)->first();
            if (!empty($user)) {
                // $token = rand(100000,999999);
                DB::table('password_resets')->where(['email' => $request->email])->delete();
                $token = 123456;
                DB::table('password_resets')->insert([
                    'email' => $user->email,
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
                // $user->notify(new PasswordResetNotification($token));
                return response()->json([
                    'status' => 1,
                    'message' => 'OTP verification code has been sent to your email address',
                    'data' => ['user_id' => $user->id]
                ], 200);
            } else {
                return response()->json([
                    'status' => 0,
                    // 'message'=>'Your Account Is Not Verified Please Verify Your Account...!'
                    'message' => 'User not found'
                ], 400);
            }
        }
    }

    /** Forgot password resend OTP */

    public function forgotPasswordResendOtp(Request $request)
    {
        $controls = $request->all();
        $rules = array(
            "email" => "required|exists:users,email"
        );
        $customMessages = [
            'required' => 'The :attribute is required.',
            'exists' => 'The :attribute does not exist',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }

        $user = User::where('email', $request->email)->first();

        // $token = rand(100000,999999);
        $token = 123456;
        DB::table('password_resets')->where(['email' => $request->email])->delete();
        DB::table('password_resets')->insert([
            'email' => $user->email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);
        // $user->notify(new PasswordResetNotification($token));

        return response()->json(['status' => 1, 'message' => 'We have resend  OTP verification code at your email address'], 200);
    }


    /** Reset password */

    public function resetPassword(Request $request)
    {
        $customMessages = [
            'required' => ":attribute can't be empty",
            'password.regex' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.min' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'password.max' => 'Password must be 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
        ];

        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'password' => 'required|min:8|max:255|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/'
        ], $customMessages);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        $user_email = User::where(['id' => $request->user_id])->first();
        $check_otp = DB::table('password_resets')->where(['email' => $user_email->email])->first();
        if ($check_otp) {
            $user = User::where('email', $check_otp->email)->first();
            $user->password = bcrypt($request->password);
            $user->save();
            DB::table('password_resets')->where(['email' => $user_email->email])->delete();
            return response()->json(['status' => 1, 'message' => "Password updated successfully"], 200);
        } else {
            return response()->json(['status' => 0, 'message' => "User Not Found"], 400);
        }
    }


    /** Change password */
    public function changePassword(Request $request)
    {
        $customMessages = [
            'email.email' => 'Please enter valid email address.',
            'required' => ":attribute can't be empty",
            'new_password.regex' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'new_password.min' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'new_password.max' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.regex' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.min' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.max' => 'Password must be of 8 characters long and contain at least 1 uppercase, 1 lowercase, 1 digit and 1 special character',
            'confirm_password.required' => ':attribute field is required',
            'confirm_password.same' => 'New Password and Confirm Password must be same',

        ];

        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:8|max:255|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/|regex:/[@$!%*#?&]/',
            'confirm_password' => 'required|same:new_password'
        ], $customMessages);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        if ($request->old_password == $request->new_password) {
            return $this->error("Old password and new password can't be same", 400);
        }

        if (Hash::check($request->old_password, auth()->user()->password)) {
            $update_password = $request->user()->update(['password' => Hash::make($request->new_password)]);
            if ($update_password) {
                return $this->success('Password updated successfully.');
            } else {
                return $this->error('Something went wrong.', 400);
            }
        } else {
            return $this->error('Old password is incorrect.', 400);
        }
    }

    /** Logout */

    public function logout(Request $request)
    {
        $user_id = auth()->user()->id;
        $user_obj = User::whereId($user_id)->count();

        if ($user_obj > 0) {
            // $sentum_delete = $request->user()->currentAccessToken()->delete();
            $sentum_delete = $request->user()->tokens()->delete();
            if ($sentum_delete) {
                $update_user = User::whereId($user_id)->update(['device_type' => null, 'device_token' => null]);
                if ($update_user) {
                    return $this->success('User logout successfully.');
                } else {
                    $this->error('Sorry there is some problem while updating user data.', 400);
                }
            } else {
                $this->error('Sorry there is some problem while deleting old token.', 400);
            }
        } else {
            return $this->error('User not found', 404);
        }
    }


    /** Social login */

    public function socialAuth(Request $request)
    {
        $controls = $request->all();
        $rules = array(
            'access_token' => 'required',
            'provider' => 'required|in:facebook,google,apple,phone',
            'device_type' => 'required',
            'device_token' => 'required',
            'email' => 'nullable',
            'name' => 'nullable',
            'role' => 'required',
            'business_name' => 'nullable'
        );
        $customMessages = [
            'required' => 'The :attribute field is required.',
            'unique' => 'The :attribute already exists',
            'exists' => 'The :attribute does not exist',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }

        $user_social_token = $request->access_token;

        $user = User::where('user_social_token', $user_social_token)->where('user_social_type', $request->provider)->first();

        if (!$user) {
            //            dd('yes');
            $user = new User();
            $user->first_name = ($request->name) ? $request->name : null;
            $user->last_name = ($request->name) ? $request->name : null;
            $user->email = ($request->email) ? $request->email : null;
            $user->email_verified_at = Carbon::now();
            $user->business_name = $request->business_name;
            $user->phone_number = $request->phone_number;

            $user->role = $request->role;
            $user->device_type = $request->device_type;
            $user->device_token = $request->device_token;
            $user->account_verified = 1;

            $user->is_social = 'yes';
            $user->user_social_token = $user_social_token;
            $user->user_social_type = $request->provider;
            $user->save();
        } else {

            $user->device_type = $request->device_type;
            $user->device_token = $request->device_token;
            $user->save();

            if ($user->role != $request->role) {
                return $this->error('Invalid credentials', 401);
            }
        }

        $user->tokens()->delete();
        $token = $user->createToken('authToken')->plainTextToken;
        return response()->json([
            'status' => 1,
            'message' => 'Login successfully',
            'data' => User::selectRaw('*,(SELECT ROUND(AVG(rating), 1) from feedbacks where user_id = users.id) as avg_rating')->whereId($user->id)->first(),
            'token' => $token
        ], 200);
    }


    /** Delete account */

    public function DeleteAccount()
    {
        $user_id = auth()->user()->id;
        $delete = User::where('id', $user_id)->delete();
        if ($delete) {
            return $this->success('Account deleted successfully.');
        } else {
            return $this->error('Account not found', 404);
        }
    }


    /** User profile */

    public function userProfile(Request $request)
    {

        $user_id =  auth()->user()->id;

        $user_profile = DB::table('users')->where("id", "=", $user_id)->first();

        if (!empty($user_id)) {
            return response()->json([
                'status' => 1,
                'message' => 'user profile',
                'data' => $user_profile,
            ]);
        }

        return response()->json([
            'status' => 1,
            'message' => 'user profile not found...!',
            'data' => $user_profile,
        ]);
    }


    /** Complete profile */


    public function completeProfile(Request $request)
    {
        $userId = auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable',
            'last_name' => 'nullable',
            'address' => 'nullable',
            'profile_image' => 'nullable',
            'phone_number' => 'required',
            'license_no' => 'nullable',
            'discipline' => 'nullable',
            'rates' => 'nullable',
            'fax' => 'nullable'
        ]);
        if ($validator->fails()) {
            return $this->error($validator->errors()->all()[0], 400);
        }

        if ($request->hasFile('profile_image')) {
            $imageName = time() . '.' . $request->profile_image->getClientOriginalExtension();
            $request->profile_image->move(public_path('/uploadedimages'), $imageName);
            $file_path = asset('uploadedimages') . "/" . $imageName;
            //$user->profile_image=$imageName;

            $submition_data = $request->all();
            $submition_data['profile_image'] = $file_path;
            $submition_data['is_profile_complete'] = '1';
        } else {
            $submition_data = $request->all();
            $submition_data['is_profile_complete'] = '1';
        }

        $update_user = User::whereId($userId)->update($submition_data);

        if ($update_user) {
            return $this->success('Profile completed successfully.', User::selectRaw('*,(SELECT ROUND(AVG(rating), 1) from feedbacks where user_id = users.id) as avg_rating')->whereId($userId)->first());
        } else {
            $this->error('Sorry there is some problem while updating profile data.', 400);
        }
    }

    /** Active Card */

    public function activeCard(Request $request)
    {
        $user = Auth::user();
        $controls = $request->all();
        $rules = array(
            "card_id" => "nullable"
        );
        $customMessages = [
            'required' => 'The :attribute  is required.',
            'exists' => 'The :attribute is Not Exists',
        ];
        $validator = Validator::make($controls, $rules, $customMessages);
        if ($validator->fails()) {
            return response()->json(['status' => 0, 'message' => $validator->errors()->all()[0]], 400);
        }

        if ($request->card_id == null) {
            $update = User::where('id', $user->id)->update(["card_id" => null, "is_card" => 0]);
        } else {
            $update = User::where('id', $user->id)->update(["card_id" => $request->card_id, "is_card" => 1]);
        }


        if ($update) {
            return $this->success('Card updated Successfully.');
        } else {
            return $this->error('Card not updated.', 400);
        }
    }
}
