<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ReceptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::post('/auth/login', [UserAuthController::class, 'login']);
Route::post('/auth/register', [UserAuthController::class, 'register']);
Route::get('/departments', [DepartmentController::class, 'getAllDepartments']);
Route::get('/doctors', [DoctorController::class, 'getAllDoctors']);



Route::middleware(['ensure.auth'])->group(function () {
    Route::get('/auth/logout', [UserAuthController::class, 'logout']);
    Route::get('/appointments/available/doctors/{id}', [AppointmentController::class, 'getAvailableAppointmentsForDoctor']);
    Route::put('/users', [UserAuthController::class, 'updateUserInfo']);
});


Route::middleware(['ensure.auth', 'user.type:patient'])->post('/comments', [CommentController::class, 'createComment']);


Route::middleware(['ensure.auth', 'user.type:doctor'])->group(function () {
    Route::put('/medical_records/{id}', [MedicalRecordController::class, 'editMedicalRecord']);
});

Route::middleware(['ensure.auth', 'user.type:reception'])->group(function () {
    Route::get('/appointments/visited/{id}', [AppointmentController::class, 'visitedAppointment']);
    Route::post('/receptions/appointments', [AppointmentController::class, 'createAppointmentByPatientEmail']);
    Route::get('/invoices', [InvoiceController::class, 'getAllInvoices']);
    Route::put('/invoices/{id}', [InvoiceController::class, 'editInvoice']);
    Route::get('/invoices/search', [InvoiceController::class, 'search']);
    Route::get('/patients',  [PatientController::class, 'getAllPatients']);
    Route::get('patients/search', [PatientController::class, 'search']);
});

Route::middleware(['ensure.auth', 'user.type:admin'])->group(function () {

    // Department
    Route::post('/departments', [DepartmentController::class, 'createDepartment']);
    Route::post('/departments/{id}', [DepartmentController::class, 'editDepartment']);
    Route::delete('/departments/{id}', [DepartmentController::class, 'deleteDepartment']);
    Route::get('/departments/search', [DepartmentController::class, 'search']);



    // Doctor
    Route::post('/doctors', [DoctorController::class, 'createDoctor']);
    Route::post('/doctors/{id}', [DoctorController::class, 'editDoctor']);
    Route::delete('/doctors/{id}', [DoctorController::class, 'deleteDoctor']);
    Route::get('/doctors/search', [DoctorController::class, 'search']);

    // Reception
    Route::get('/receptions', [ReceptionController::class, 'getAllReceptions']);
    Route::post('/receptions', [ReceptionController::class, 'createRecpetion']);
    Route::post('/receptions/{id}', [ReceptionController::class, 'editReception']);
    Route::delete('/receptions/{id}', [ReceptionController::class, 'deleteReception']);
    Route::get('/receptions/search', [ReceptionController::class, 'search']);

    // Comments
    Route::get('/comments', [CommentController::class, 'getAllComments']);
});

// patient reception doctor
Route::middleware(['ensure.auth', 'user.type:patient,reception,doctor'])->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'getAllAppointments']);
    Route::get('/appointments/search', [AppointmentController::class, 'search']);
});

// patient reception
Route::middleware(['ensure.auth', 'user.type:patient,reception'])->post('/appointments', [AppointmentController::class, 'createAppointment']);


// reception doctor
Route::middleware(['ensure.auth', 'user.type:reception,doctor'])->group(function () {
    Route::get('/appointments/cancel/{id}', [AppointmentController::class, 'cancelAppointment']);
    Route::get('/medical_records', [MedicalRecordController::class, 'getMedicalRecords']);
    Route::get('/medical_records/search', [MedicalRecordController::class, 'search']);
});
