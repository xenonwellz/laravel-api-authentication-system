<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'user_name' => 'min:4|max:20',
            'avatar' => 'image',
            'email' => 'email',
            'password' => 'string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $message = [];
        $user = auth('api')->user();

        if ($request->email) {
            if (User::where('email', $request->email)->exists()) {
                return response()->json('Email is already registered', 422);
            }
            $verify_mail_token = random_int(100000, 999999);
            $user->email = $request->email;
            $user->email_verified_at = null;
            $token =   $verify_mail_token;
            \Mail::to($request->email)->send(new \App\Mail\VerifyMail($token));

            array_push($message, 'Email Updated successfully, Please verify email');
        }

        if ($request->user_name) {
            if (User::where('user_name', $request->user_name)->exists()) {
                return response()->json('Username is already registered', 422);
            }
            $user->user_name = $request->user_name;

            array_push($message, 'Userrname Updated successfully');
        }

        if ($request->name) {
            $user->name = $request->name;

            array_push($message, 'Name Updated successfully');
        }
        if ($request->avatar) {
            $filename = time() . '.' . $request->avatar->getClientOriginalExtension();
            $request->avatar->move(public_path('images'), $filename);

            $user->avatar = $filename;

            array_push($message, 'Avatar Updated successfully');
        }

        if ($request->password) {
            $user->avatar = Hash::make($request->password);

            array_push($message, 'Password Updated successfully');
        }

        $user->save();

        return $message;
    }
}
