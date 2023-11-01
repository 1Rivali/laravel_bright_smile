<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "first_name" => ['required'],
            "last_name" => ['required'],
            "email" => ['required', 'email', 'unique:users'],
            "password" => ['required', 'min:8'],
            "date_of_birth" => ['required', 'date_format:Y-m-d'],
            "address" => ['required', 'max:255'],
            "phone" => ['required', 'max:20'],
            "gender" => ['required'],
            "martial_status" => ['required', 'in:Single,Married'],
            "health_status" => ['required']
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages(),
            ], 400);
        }
        try {
            $validated = $validator->validated();
            // hashing password before creating user
            $validated['password'] = Hash::make($validated['password']);

            // creating the user
            $user = new User([
                "first_name" => $validated["first_name"],
                "last_name" => $validated["last_name"],
                "email" => $validated["email"],
                "password" => $validated["password"],
                "date_of_birth" => $validated["date_of_birth"],
                "address" => $validated["address"],
                "phone" => $validated["phone"],
                "gender" => $validated["gender"],
            ]);
            $user->save();
            // creating the token from the user
            $token = $user->createToken('API TOKEN')->plainTextToken;

            // creating patient
            $patient = new Patient([
                "marital_status" => $validated['martial_status'],
                "health_status" => $validated['health_status']
            ]);

            // assigning patient to the created user
            $user->patient()->save($patient);

            // returning success message and adding the token to the response header
            return response()->json(
                [
                    'success' => true,
                    'message' => 'User Created Successfuly',
                    'token' => $token,
                    'data' => $user
                ],
                201,
            );
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }


    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_type' => ['required', 'in:admin,patient,doctor,reception'],
            'email' => ['required', 'exists:users,email'],
            'password' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages()
            ], 400);
        }
        try {
            $validated = $validator->validated();
            // trying to login the user with the provided credentials
            // added [$remember=true] to keep the user logged in until he logout
            if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $remember = true)) {

                // getting the logged in user
                $user = Auth::guard('sanctum')->user();

                switch ($user->user_type) {
                    case 'doctor':
                        $user['doctor'] = $user->doctor;
                        break;
                    case 'patient':
                        $user['patient'] = $user->patient;
                        break;
                    case 'reception':
                        $user['reception'] = $user->reception;
                        break;

                    default:
                        # code...
                        break;
                }
                // checking if the user_type in the database matches the user_type sent in the request 
                if ($user->user_type !== $validated['user_type']) {
                    return response()->json(
                        [
                            'message' => 'Invalid User Type'
                        ],
                        401
                    );
                }

                // creating the token
                $token = $user->createToken('API TOKEN')->plainTextToken;

                // returning success message with the token in the response header
                return response()->json(
                    [
                        'message' => 'User Logged In Successfuly',
                        'data' => $user,
                        'token' => $token
                    ],
                    200,
                );
            }

            // if the [Auth::attempt()] failed we send response with failure message 
            return response()->json(
                [
                    'message' => 'Wrong Credentials'
                ],
                401
            );
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $user->tokens()->delete();
            return response()->json([
                "success" => true,
                "message" => "Logged out successfuly"
            ], 204);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function updateUserInfo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => ['required', 'email'],
                "address" => ['required', 'max:255'],
                "phone" => ['required', 'max:20'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->messages(),
                ], 400);
            }
            $validated = $validator->validated();
            $user = Auth::guard('sanctum')->user();
            $user->update([
                "email" => $validated['email'],
                "address" => $validated['address'],
                "phone" => $validated['phone'],
            ]);
            return response()->json([
                'status' => 'true',
                'message' => 'User info updated successfuly',
                'date' => $user
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
