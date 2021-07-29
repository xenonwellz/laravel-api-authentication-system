<?php

namespace App\Http\Controllers;

use Mail;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'user_name' => 'required|min:4|max:20',
            'admin_id' => 'required|integer',
            'avatar' => 'required|dimensions:width=256|dimensions:height=256|image',
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (DB::table('sign_up')->where('admin_id', $request->admin_id)->where('email', $request->email)) {

            $filename = time() . '.' . $request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('images'), $filename);
            $verify_mail_token = random_int(100000, 999999);

            $user = new User([
                'name' => $request->name,
                'user_name' => $request->user_name,
                'email' => $request->email,
                'role' => 'user',
                'verify_mail_token' => $verify_mail_token,
                'avatar' => $filename,
                'password' => Hash::make($request->password)
            ]);

            $user->save();
            $token =   $verify_mail_token;
            \Mail::to($request->email)->send(new \App\Mail\VerifyMail($token));
            return response()->json(['message' => 'User created Successfully, please verify mail']);
        }
        return response()->json(['message' => 'User not scheduled for sign_up']);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)->first();

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Please verify your account'], 401);
        }

        if (!Auth::attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $tokenResult = $user->createToken('Personl Access Token');
        $token = $tokenResult->token;
        $token->expires_at = Carbon::now()->addWeeks(1);
        $token->save();

        return response()->json(['data' => [
            'user' => Auth::user(),
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'token_expiry' => Carbon::parse($tokenResult->token->expires_at)->toDateTimeString()
        ]]);
    }

    public function register_admin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'user_name' => 'required|min:4|max:20',
            'avatar' => 'required|dimensions:width=256|dimensions:height=256|image',
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $filename = time() . '.' . $request->avatar->getClientOriginalExtension();
        $request->avatar->move(public_path('images'), $filename);
        $verify_mail_token = random_int(100000, 999999);

        $user = new User([
            'name' => $request->name,
            'user_name' => $request->user_name,
            'verify_mail_token' => $verify_mail_token,
            'email' => $request->email,
            'role' => 'admin',
            'avatar' => $filename,
            'password' => Hash::make($request->password)
        ]);

        $user->save();
        $token =   $verify_mail_token;
        \Mail::to($request->email)->send(new \App\Mail\VerifyMail($token));
        return response()->json(['message' => 'Admin created Successfully, please verify mail']);
    }

    public function resend_otp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $verify_mail_token = random_int(100000, 999999);

            $user->verify_mail_token =  $verify_mail_token;
            $user->save();

            $token =   $verify_mail_token;
            \Mail::to($request->email)->send(new \App\Mail\VerifyMail($token));

            return response()->json(['message' => 'Email has been sent to you to verify your email']);
        }
        return response()->json(['message' => 'Unsuccessfull']);
    }

    public function verify_mail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->where('verify_mail_token', $request->token)->first();

        if ($user) {
            $user->email_verified_at = Carbon::now()->toDateTimeString();
            $user->save();

            return response()->json(['message' => 'Your Email has been verified']);
        }

        return response()->json(['message' => 'Couldnt verify your email']);
    }

    public function create_verify_link(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!auth('api')->user()) {
            return response()->json(['message' => 'Action Unauthorized']);
        }

        if (DB::table('sign_up')->where('email', $request->email) || DB::table('users')->where('email', $request->email)) {
            return response()->json(['message' => 'Email has been setup']);
        }
        DB::insert('insert into sign_up (admin_id, email) values (?, ?)', [auth('api')->user()->id, $request->email]);

        return response()->json(['message' => 'Email can now sign up under admin']);
    }
}
