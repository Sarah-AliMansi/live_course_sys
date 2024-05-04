<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;




class RegisterController extends BaseController
{
    /**
     * Register a new user (either teacher or student).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return $this->sendResponse($users, 'Displaying all users data');
    }

    public function register(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users', 'email:rfc,dns'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['required', 'string', 'max:255'],
            'subject' => ($request->role == 'teacher') ? ['required', 'string'] : 'nullable',
            'photos' => ($request->role == 'teacher') ? ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'] : 'nullable',
            'role' => 'required|in:student,teacher',

        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }

        // Generate a random code for teacher
        $code = ($request->role == 'teacher') ? Str::random(6) : null;

        // Create user based on role (teacher or student)
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'code' => $code,
                'subject' => ($request->role == 'teacher') ? $request->subject : null,
                'photos' => ($request->hasFile('photos') && $request->role == 'teacher') ?$request->file('photos')->store('public/teacher') : null,
                'role' =>$request->role,
            ]);






            if ($request->role === 'teacher') {
                Teacher::create([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'phone' => $request->phone,
                    'code' => $code,
                    'subject' => $request->subject,
                    'photos' => ($request->hasFile('photos')) ? $request->file('photos')->store('public/teacher') : null,

                ]);
            } else {
                Student::create([
                    'teacher_id' => $request->teacher_id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'phone' => $request->phone,
                ]);
            }

            if ($request->role == 'student' && ($request->has('subject') || $request->hasFile('photos'))) {

                return response()->json(['message' => 'You are not allowed to set values for subject or photos.'], 403);
            } else {
            }


            $success = [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'code' => $code,
            ];

return $this->sendResponse($success, 'User registered successfully.');        }





        public function login(Request $request)

        {
            $credentials = $request->only('email', 'password');

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                $token = $user->createToken('authToken')->plainTextToken;

                return response()->json(['token' => $token,'user'=>$user]);
            }

            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
