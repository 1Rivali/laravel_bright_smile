<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DoctorController extends Controller
{
    public function getAllDoctors()
    {
        try {
            $doctors = Doctor::with('user')->get();
            if (sizeof($doctors) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No doctors found',
                ], 204);
            }
            return response()->json([
                'success' => true,
                'message' => 'Doctors fetched successfully',
                'data' => $doctors,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function editDoctor(Request $request, $id)
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
            $doctor = Doctor::with('user')->find($id);
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid doctor id',
                ], 404);
            }
            if ($request->file('image')) {
                $imagePath = $request->file('image')->store('images', 'public');
                $imageUrl = asset('uploads/' . $imagePath);
                $doctor->update([
                    'shift' => $validatedJsonData['shift'],
                    'working_days' => $validatedJsonData['working_days'],
                    'image' => $imageUrl
                ]);
            } else {
                $doctor->update([
                    'shift' => $validatedJsonData['shift'],
                    'working_days' => $validatedJsonData['working_days'],
                ]);
            }

            $user = $doctor->user;

            $user->update([
                'address' => $validatedJsonData['address'],
                'phone' => $validatedJsonData['phone']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Doctor updated successfully',
                'data' => $doctor
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
    public function createDoctor(Request $request)
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
            'specialization' => ['required'],
            'department_id' => ['required', 'exists:departments,id'],
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
                "first_name" => $validatedJsonData["first_name"],
                "last_name" => $validatedJsonData["last_name"],
                "email" => $validatedJsonData["email"],
                "password" => $validatedJsonData["password"],
                "date_of_birth" => $validatedJsonData["date_of_birth"],
                "address" => $validatedJsonData["address"],
                "phone" => $validatedJsonData["phone"],
                "gender" => $validatedJsonData["gender"],
                "user_type" => "doctor"
            ]);
            $user->save();
            $department = Department::find($validatedJsonData['department_id']);

            $imagePath = $request->file('image')->store('images', 'public');
            $imageUrl = asset('uploads/' . $imagePath);

            $doctor = new Doctor([
                "shift" => $validatedJsonData['shift'],
                "working_days" => $validatedJsonData['working_days'],
                "specialization" => $validatedJsonData['specialization'],
                'image' => $imageUrl,
            ]);
            $doctor->department()->associate($department);

            $user->doctor()->save($doctor);
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Doctor Created Successfuly',
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
    public function deleteDoctor(Request $request, $id)
    {
        try {
            $doctor = Doctor::find($id);
            $user = $doctor->user;
            User::destroy($user->id);
            return response()->json([
                'status' => true,
                'message' => 'Doctor deleted successfuly'
            ]);
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
        $results = User::with('doctor')->where('user_type', 'doctor')
            ->where(function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%$searchTerm%")->orWhere('last_name', 'like', "%$searchTerm%");
            })->get();
        return response()->json($results);
    }
}
