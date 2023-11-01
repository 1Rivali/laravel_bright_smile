<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function getAllDepartments()
    {
        try {
            $departments = Department::all();
            if (sizeof($departments) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No departments found',
                ], 204);
            }
            return response()->json([
                'success' => true,
                'message' => 'Departments fetched successfully',
                'data' => $departments,
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
        $results = Department::where('name', 'like', "%$searchTerm%")->get();
        return response()->json($results);
    }

    public function editDepartment(Request $request, $id)
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
            "name" => ['required'],
            "number" => ['required'],
            "phone" => ['required'],
        ]);
        if ($jsonDataValidator->fails()) {
            return response()->json([
                'message' => $jsonDataValidator->messages(),
            ], 400);
        }
        try {
            $validatedJsonData = $jsonDataValidator->validated();

            $department = Department::find($id);
            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid department id',
                ], 404);
            }
            if ($request->file('image')) {
                $imagePath = $request->file('image')->store('images', 'public');
                $imageUrl = asset('uploads/' . $imagePath);
                $department->update([
                    "name" => $validatedJsonData['name'],
                    "number" => $validatedJsonData['number'],
                    "phone" => $validatedJsonData['phone'],
                    "image" => $imageUrl
                ]);
            } else {
                $department->update([
                    "name" => $validatedJsonData['name'],
                    "number" => $validatedJsonData['number'],
                    "phone" => $validatedJsonData['phone'],
                ]);
            }

            return response()->json([
                'status' => 'true',
                'message' => 'department info updated successfuly',
                'data' => $department
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function createDepartment(Request $request)
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
            "name" => ['required'],
            "number" => ['required'],
            "phone" => ['required'],
        ]);
        if ($jsonDataValidator->fails()) {
            return response()->json([
                'message' => $jsonDataValidator->messages(),
            ], 400);
        }
        try {

            $validatedJsonData = $jsonDataValidator->validated();
            $imagePath = $request->file('image')->store('images', 'public');
            $imageUrl = asset('uploads/' . $imagePath);
            $department = new Department([
                "name" => $validatedJsonData['name'],
                "number" => $validatedJsonData['number'],
                "phone" => $validatedJsonData['phone'],
                "image" => $imageUrl
            ]);
            $department->save();
            return response()->json(
                [
                    'success' => true,
                    'message' => 'Department Created Successfuly',
                    'data' => $department
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

    public function deleteDepartment(Request $request, $id)
    {
        try {
            Department::destroy($id);
            return response()->json([
                'status' => true,
                'message' => 'Department deleted successfuly'
            ], 204);
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
