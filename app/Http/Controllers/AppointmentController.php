<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends Controller
{
    public function getAllAppointments(Request $request)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $appointments = [];

            switch ($user->user_type) {
                case 'patient':
                    $appointments = Appointment::with(['doctor.user', 'doctor.department'])->where('patient_id', $user->patient->id)->get();
                    break;
                case 'doctor':
                    $appointments = Appointment::with(['doctor.user', 'doctor.department', 'patient.user'])->where('doctor_id', $user->doctor->id)->get();
                    break;
                case 'reception':
                    $appointments = Appointment::with(['doctor.user', 'doctor.department', 'patient.user'])->get();
                    break;
                default:
                    break;
            }

            if (sizeof($appointments) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No appointments found',
                ], 204);
            }

            return response()->json([
                'success' => true,
                'message' => 'Appointments fetched successfully',
                'data' => $appointments,
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
            case 'patient':
                // $results = Doctor::with(['user', 'department'])->where('a')
                $results = Appointment::with(['doctor.user', 'doctor.department'])
                    ->where('patient_id', $user->patient->id)
                    ->whereHas('doctor.user', function ($query) use ($searchTerm) {
                        $query->where(function ($query) use ($searchTerm) {
                            $query->where('first_name', 'like', "%$searchTerm%")
                                ->orWhere('last_name', 'like', "%$searchTerm%");
                        });
                    })->get();
                break;
            case 'doctor':
                $results = Appointment::with(['doctor.user', 'doctor.department', 'patient.user'])
                    ->where('doctor_id', $user->doctor->id)
                    ->whereHas('patient.user', function ($query) use ($searchTerm) {
                        $query->where(function ($query) use ($searchTerm) {
                            $query->where('first_name', 'like', "%$searchTerm%")
                                ->orWhere('last_name', 'like', "%$searchTerm%");
                        });
                    })->get();
                break;
            case 'reception':
                $results = Appointment::with(['doctor.user', 'doctor.department', 'patient.user'])
                    ->whereHas('patient.user', function ($query) use ($searchTerm) {
                        $query->where(function ($query) use ($searchTerm) {
                            $query->where('first_name', 'like', "%$searchTerm%")
                                ->orWhere('last_name', 'like', "%$searchTerm%");
                        });
                    })->get();
                break;
            default:
                break;
        }
        return response()->json($results);
    }



    public function createAppointment(Request $request)
    {

        try {
            $user = Auth::guard('sanctum')->user();
            $validated = null;
            $patient = null;

            if ($user->user_type === "patient") {
                $validator = Validator::make($request->all(), [
                    'doctor_id' => ['required', 'exists:doctors,id'],
                    'appointment_date' => ['required', 'date_format:Y-m-d'],
                    'appointment_time' => ['required', 'date_format:H:i:s'],
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'message' => $validator->messages(),
                    ], 400);
                }
                $validated = $validator->validated();
                $patient = $user->patient;

                $haveAppointment = Appointment::where('patient_id', $patient->id)->where('status', 'waiting')->get();

                if (sizeof($haveAppointment) !== 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'you already have an appointment'
                    ], 400);
                }
            } else {
                $validator = Validator::make($request->all(), [
                    "doctor_id" => ['required', 'exists:doctors,id'],
                    "appointment_date" => ['required', 'date_format:Y-m-d'],
                    "appointment_time" => ['required', 'date_format:H:i:s'],
                    "patient_first_name" => ['required'],
                    "patient_last_name" => ['required'],
                    "patient_email" => ['unique:users,email'],
                    "patient_password" => ['required', 'min:8'],
                    "patient_date_of_birth" => ['required', 'date_format:Y-m-d'],
                    "patient_address" => ['required', 'max:255'],
                    "patient_phone" => ['required', 'max:20'],
                    "patient_gender" => ['required'],
                    "patient_martial_status" => ['required', 'in:Single,Married'],
                    "patient_health_status" => ['required'],
                ]);
                if ($validator->fails()) {
                    return response()->json([
                        'message' => $validator->messages(),
                    ], 400);
                }
                $validated = $validator->validated();
                $validated['patient_password'] = Hash::make($validated['patient_password']);
                $newUser = new User([
                    'first_name' => $validated['patient_first_name'],
                    'last_name' => $validated['patient_last_name'],
                    'email' => $validated['patient_email'],
                    'password' => $validated['patient_password'],
                    'date_of_birth' => $validated['patient_date_of_birth'],
                    'phone' => $validated['patient_phone'],
                    'address' => $validated['patient_address'],
                    'gender' => $validated['patient_gender'],
                ]);

                $newUser->save();

                $newPatient = new Patient([
                    "martial_status" => $validated['patient_martial_status'],
                    "health_status" => $validated['patient_health_status']
                ]);
                $newUser->patient()->save($newPatient);
                $patient = $newUser->patient;
            }

            $appointmentExist = Appointment::where('doctor_id', $validated['doctor_id'])
                ->where('time', $validated['appointment_time'])
                ->where('date', $validated['appointment_date'])->first();

            if ($appointmentExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not available'
                ], 400);
            }

            $doctor = Doctor::find($validated['doctor_id']);


            $appointment = new Appointment([
                'date' => $validated['appointment_date'],
                'time' => $validated['appointment_time']
            ]);

            $appointment->patient()->associate($patient);
            $appointment->doctor()->associate($doctor);


            $appointment->save();

            return response()->json([
                'success' => true,
                'message' => 'Appointments created successfully',
                'data' => $appointment,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function createAppointmentByPatientEmail(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_email' => ['required', 'exists:users,email'],
                'doctor_id' => ['required', 'exists:doctors,id'],
                'appointment_date' => ['required', 'date_format:Y-m-d'],
                'appointment_time' => ['required', 'date_format:H:i:s'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->messages(),
                ], 400);
            }
            $validated = $validator->validated();
            $user = User::where('email', $validated['patient_email'])->first();
            $patient = $user->patient;

            $haveAppointment = Appointment::where('patient_id', $patient->id)->where('status', 'waiting')->get();

            if (sizeof($haveAppointment) !== 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'you already have an appointment'
                ], 400);
            }

            $appointmentExist = Appointment::where('doctor_id', $validated['doctor_id'])
                ->where('time', $validated['appointment_time'])
                ->where('date', $validated['appointment_date'])->first();

            if ($appointmentExist) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not available'
                ], 400);
            }

            $doctor = Doctor::find($validated['doctor_id']);


            $appointment = new Appointment([
                'date' => $validated['appointment_date'],
                'time' => $validated['appointment_time']
            ]);

            $appointment->patient()->associate($patient);
            $appointment->doctor()->associate($doctor);


            $appointment->save();

            return response()->json([
                'success' => true,
                'message' => 'Appointments created successfully',
                'data' => $appointment,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function cancelAppointment(Request $request, $id)
    {
        try {
            $appointment = Appointment::find($id);
            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid appointment id',
                ], 404);
            }

            $appointment->status = 'canceled';
            $appointment->save();
            return response()->json([
                'success' => true,
                'message' => 'appointment canceled successfully',
                'data' => $appointment,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }



    public function visitedAppointment(Request $request, $id)
    {

        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid appointment id',
                ], 404);
            }
            $appointment->status = 'visited';
            $appointment->save();

            $patient = $appointment->patient;
            $reception = Auth::guard('sanctum')->user()->reception;

            $invoice = new Invoice([]);

            $invoice->patient()->associate($patient);
            $invoice->reception()->associate($reception);
            $invoice->save();

            $medicalRecordExist = MedicalRecord::whereHas('appointment', function ($query) use ($appointment) {
                $query->where('patient_id', $appointment->patient->id);
            })->get();

            if (sizeof($medicalRecordExist) === 0) {
                $medicalRecord = new MedicalRecord([]);
                $medicalRecord->save();
                $appointment->medicalRecord()->associate($medicalRecord);
                $appointment->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'status updated successfuly',
            ], 200);
        } catch (\Throwable $th) {
            dd($th);
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }

    public function getAvailableAppointmentsForDoctor(Request $request, $id)
    {
        $dayHours = [
            '10:00:00',
            '10:30:00',
            '11:00:00',
            '11:30:00',
            '12:00:00',
            '12:30:00',
            '13:00:00',
            '13:30:00',
            '14:00:00',
            '14:30:00'
        ];
        $nightHours = [
            '15:00:00',
            '15:30:00',
            '16:00:00',
            '16:30:00',
            '17:00:00',
            '17:30:00',
            '18:00:00',
            '18:30:00',
            '19:00:00',
            '19:30:00'
        ];
        try {
            $doctor = Doctor::find($id);

            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid doctor id',
                ], 404);
            }

            $workingDaysMap = [
                'STT' => ['Sunday', 'Tuesday', 'Thursday'],
                'MWS' => ['Monday', 'Wednesday', 'Saturday'],
            ];

            if (!array_key_exists($doctor->working_days, $workingDaysMap)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid working days configuration for the doctor',
                ], 400);
            }

            $workingDays = $workingDaysMap[$doctor->working_days];

            $takenAppointments = Appointment::select('time', 'date')
                ->where('doctor_id', $id)
                ->where(function ($query) use ($doctor) {
                    $operator = '';
                    $time = '';
                    if ($doctor->shift === 'day') {
                        $operator = '<=';
                        $time = '14:30:00';
                    } else {
                        $operator = '>=';
                        $time = '15:00:00';
                    }
                    $query->where('time', $operator, $time);
                })
                ->get();

            $availableAppointments = [];

            foreach ($workingDays as $day) {
                $takenTimes = [];

                foreach ($takenAppointments as $appointment) {
                    $date = Carbon::parse($appointment->date);
                    $appointmentDay = $date->format('l');

                    if ($appointmentDay === $day) {
                        array_push($takenTimes, $appointment->time);
                    }
                }
                if ($doctor->shift === 'day') {
                    $availableTimes = array_values(array_diff($dayHours, $takenTimes));
                } else {
                    $availableTimes = array_values(array_diff($nightHours, $takenTimes));
                }
                $availableAppointments[$day] = $availableTimes;
            }

            return response()->json([
                'status' => true,
                'message' => 'Available appointments fetched successfully',
                'appointments' => $availableAppointments,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
