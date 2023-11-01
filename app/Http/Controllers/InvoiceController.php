<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function getAllInvoices()
    {
        try {
            $invoices = Invoice::with('patient.user')->get();
            if (sizeof($invoices) === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No invoices found',
                ], 204);
            }
            return response()->json([
                'success' => true,
                'message' => 'Invoices fetched successfully',
                'data' => $invoices,
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
        $results = Invoice::with('patient.user')
            ->whereHas('patient.user', function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%");
            })->get();
        return response()->json($results);
    }
    public function editInvoice(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_date' => ['required', 'date_format:Y-m-d'],
                'total_amount' => ['required'],
                'paid_amount' => ['required'],
                'remaining_amount' => ['required'],
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'message' => $validator->messages(),
                ], 400);
            }
            $validated = $validator->validated();

            $invoice = Invoice::with('patient.user')->find($id);
            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Invoice id',
                ], 404);
            }
            $invoice->update([
                'payment_date' => $validated['payment_date'],
                'total_amount' => $validated['total_amount'],
                'paid_amount' => $validated['paid_amount'],
                'remaining_amount' => $validated['remaining_amount']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error',
            ], 500);
        }
    }
}
