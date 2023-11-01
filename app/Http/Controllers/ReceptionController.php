<?php

namespace App\Http\Controllers;

use App\Models\Reception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ReceptionController extends Controller
{
    public function getAllReceptions()
    {
        try {
            $receptions = Reception::with('user')->get();
            if (sizeof($receptions) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No receptions found',
                ], 204);
            }
            return response()->json([
                'success' => true,
                'message' => 'receptions fetched successfully',
                'data' => $receptions,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
    public function search(Request $request)
    {
        $searchTerm = $request->input('q');
        $results = User::with('reception')->where('user_type', 'reception')
            ->where('first_name', 'like', "%$searchTerm%")
            ->orWhere('last_name', 'like', "%$searchTerm%")
            ->get();
        return response()->json($results);
    }
    public function editReception(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image' => ['image', 'max:2048'],
            'data' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages(),
            ], 400);
        }
        $jsonData = json_decode($request->input('data'), true);

        $jsonDataValidator = Validator::make($jsonData, [
            'address' => ['required'],
            'phone' => ['required', 'max:20'],
            'shift' => ['required', 'in:day,night'],
            'working_days' => ['required', 'in:STT,MWS'],
        ]);
        if ($jsonDataValidator->fails()) {
            return response()->json([
                'message' => $jsonDataValidator->messages(),
            ], 400);
        }
        try {
            $validatedJsonData = $jsonDataValidator->validated();
            $reception = Reception::find($id);
            if (!$reception) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reception id',
                ], 404);
            }
            if ($request->file('image')) {
                $imagePath = $request->file('image')->store('images', 'public');
                $imageUrl = asset('uploads/' . $imagePath);
                $reception->update([
                    'shift' => $validatedJsonData['shift'],
                    'working_days' => $validatedJsonData['working_days'],
                    'image' => $imageUrl
                ]);
            } else {
                $reception->update([
                    'shift' => $validatedJsonData['shift'],
                    'working_days' => $validatedJsonData['working_days'],
                ]);
            }

            $user = $reception->user;

            $user->update([
                'address' => $validatedJsonData['address'],
                'phone' => $validatedJsonData['phone']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reception updated successfully',
                'data' => $reception
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function createRecpetion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => ['required', 'image', 'max:2048'],
            'data' => ['required'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages(),
            ], 400);
        }
        $jsonData = json_decode($request->input('data'), true);

        $jsonDataValidator = Validator::make($jsonData, [
            "first_name" => ['required'],
            "last_name" => ['required'],
            "email" => ['required', 'email', 'unique:users'],
            "password" => ['required', 'min:8'],
            "date_of_birth" => ['required', 'date_format:Y-m-d'],
            "address" => ['required', 'max:255'],
            "phone" => ['required', 'max:20'],
            "gender" => ['required'],
            'shift' => ['required', 'in:day,night'],
            'working_days' => ['required', 'in:STT,MWS'],
            'university_degree' => ['required']
        ]);
        if ($jsonDataValidator->fails()) {
            return response()->json([
                'message' => $jsonDataValidator->messages(),
            ], 400);
        }
        try {
            $validatedJsonData = $jsonDataValidator->validated();
            $validatedJsonData['password'] = Hash::make($validatedJsonData['password']);
            $user = new User([
                "first_name" => $validatedJsonData['first_name'],
                "last_name" => $validatedJsonData['last_name'],
                "email" => $validatedJsonData['email'],
                "password" => $validatedJsonData['password'],
                "date_of_birth" => $validatedJsonData['date_of_birth'],
                "address" => $validatedJsonData['address'],
                "phone" => $validatedJsonData['phone'],
                "gender" => $validatedJsonData['gender'],
                "user_type" => 'reception'
            ]);
            $user->save();

            $imagePath = $request->file('image')->store('images', 'public');
            $imageUrl = asset('uploads/' . $imagePath);

            $reception = new Reception([
                "shift" => $validatedJsonData['shift'],
                "working_days" => $validatedJsonData['working_days'],
                "university_degree" => $validatedJsonData['university_degree'],
                "image" => $imageUrl
            ]);
            $user->reception()->save($reception);
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Reception Created Successfuly',
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

    public function deleteReception(Request $request, $id)
    {
        try {
            $reception = Reception::find($id);
            $user = $reception->user;
            User::destroy($user->id);
            return response()->json([
                'status' => true,
                'message' => 'Reception deleted successfuly'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
