<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MedicalRecordController extends Controller
{
    public function getMedicalRecords()
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $medicalRecords = [];

            switch ($user->user_type) {
                case 'reception':
                    $medicalRecords = MedicalRecord::with(['appointment.doctor.user', 'appointment.patient.user'])->get();
                    break;
                case 'doctor':
                    $medicalRecords = MedicalRecord::with(['appointment.doctor.user', 'appointment.patient.user'])
                        ->whereHas('appointment.doctor', function ($query) use ($user) {
                            $query->where('id', $user->doctor->id);
                        })
                        ->get();
                    break;
                default:
                    break;
            }
            if (sizeof($medicalRecords) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No medical records found',
                ], 204);
            }
            return response()->json([
                'success' => true,
                'message' => 'Medical records fetched successfully',
                'data' => $medicalRecords,
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
        $user = Auth::guard('sanctum')->user();
        $searchTerm = $request->input('q');
        $results = [];

        switch ($user->user_type) {
            case 'reception':
                $results = MedicalRecord::with(['appointment.doctor.user', 'appointment.patient.user'])
                    ->whereHas('appointment.patient.user', function ($query) use ($searchTerm) {
                        $query->where('first_name', 'like', "%$searchTerm%")
                            ->orWhere('last_name', 'like', "%$searchTerm%");
                    })
                    ->get();
                break;
            case 'doctor':
                $results = MedicalRecord::with(['appointment.doctor.user', 'appointment.patient.user'])
                    ->whereHas('appointment.patient.user', function ($query) use ($searchTerm) {
                        $query->where(function ($subQuery) use ($searchTerm) {
                            $subQuery->where('first_name', 'like', "%$searchTerm%")
                                ->orWhere('last_name', 'like', "%$searchTerm%");
                        });
                    })
                    ->get();
                break;
            default:
                break;
        }
        return response()->json($results);
    }
    public function editMedicalRecord(Request $request, $id)
    {
        try {
            $medicalRecord = MedicalRecord::find($id);
            if (!$medicalRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid medical record id',
                ], 404);
            }

            $updateDiagnosis = $medicalRecord->diagnosis . " " . $request->diagnosis;
            $updatedTreatment = $medicalRecord->treatment . " " . $request->treatment;
            $medicalRecord->update([
                'diagnosis' => $updateDiagnosis,
                'treatment' => $updatedTreatment
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Record updated successfully',
                'data' => $medicalRecord
            ]);
        } catch (\Throwable $th) {

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
