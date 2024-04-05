<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\validation\Rules;
use Illuminate\Support\Facades\Validator;

class RegisterController extends BaseController

{
    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    /** get all users */
    public function index()
    {
        $users = User::all();
        return $this->sendResponse($users, 'Displaying all users data');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required','string','max:255'],
            'last_name'=>'required',
            'email' => ['required', 'email', 'unique:users', 'email:rfc,dns'],
            'password' => ['required','confirmed',Rules\password::defaults()],
            'phone'=>'required',
            'status' => 'required|in:student,teacher',
            'subject' => ($request->status == 'teacher') ? ['required', 'string'] : 'nullable',
            'photos' => ($request->status == 'teacher') ? ['required', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'] : ['nullable'],
        ]);
        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        if ($request->hasFile('photos')) {
            $photo = $request->file('photos');
            $photoPath = $photo->store('photos', 'public');
            $input['photos'] = $photoPath;
        }


        if ($request->status == 'student' && ($request->has('subject') || $request->hasFile('photos'))) {
            return $this->sendError('Validation Error.', ['message' => 'You are not allowed to set values for subject or photos.']);
        }


        // Create the user
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'phone' => $request->phone,
            'status' => $request->status,
            'subject' => ($request->status == 'teacher') ? $request->subject : null,
            'photos' => $photoPath ?? null,
        ]);




        // Generate token for the user
        $token = $user->createToken('MyApp')->plainTextToken;

        // Prepare success response
        $success = [
            'token' => $token,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
        ];

        return $this->sendResponse($success, 'User registered successfully.');
    }


    /* /**
     * Login user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // User login successful
            $user = Auth::user();
            $token = $user->createToken('MyApp')->plainTextToken;

            $success = [
                'token' => $token,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ];

            return $this->sendResponse($success, 'User logged in successfully.');
        } else {
            // User login failed
            return $this->sendError('Unauthorized.', ['error' => 'Unauthorized']);
        }
    }







}
