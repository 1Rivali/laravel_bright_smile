<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    public function getAllPatients()
    {
        try {
            $patients = Patient::with('user')->get();
            if (sizeof($patients) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Patients found',
                ], 204);
            }
            return response()->json([
                'success' => true,
                'message' => 'Patients fetched successfully',
                'data' => $patients,
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
        $results = User::with('patient')->where('user_type', 'patient')
            ->where(function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%");
            })

            ->get();
        return response()->json($results);
    }
}
