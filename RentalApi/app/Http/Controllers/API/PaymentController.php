<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * @OA\Get(
     *     path="/payments",
     *     summary="List all payments",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of payments",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $payments = Payment::with('rental')->paginate();
        return response()->json($payments);
    }

    /**
     * @OA\Post(
     *     path="/payments",
     *     summary="Create a new payment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rental_id","amount","payment_method"},
     *             @OA\Property(property="rental_id", type="integer"),
     *             @OA\Property(property="amount", type="number", format="decimal"),
     *             @OA\Property(property="payment_method", type="string", enum={"credit_card","debit_card","paypal","cash"}),
     *             @OA\Property(property="transaction_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Payment created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rental_id' => 'required|exists:rentals,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,cash',
            'transaction_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = Payment::create($request->all());
        
        return response()->json([
            'payment' => $payment,
            'message' => 'Payment created successfully'
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/payments/{id}",
     *     summary="Get payment details",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment details"
     *     )
     * )
     */
    public function show($id)
    {
        $payment = Payment::with('rental')->findOrFail($id);
        return response()->json(['payment' => $payment]);
    }

    /**
     * @OA\Put(
     *     path="/payments/{id}",
     *     summary="Update payment status",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"pending","completed","failed","refunded"}),
     *             @OA\Property(property="transaction_id", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:pending,completed,failed,refunded',
            'transaction_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payment = Payment::findOrFail($id);
        $payment->update($request->all());

        return response()->json([
            'payment' => $payment,
            'message' => 'Payment updated successfully'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/payments/{id}",
     *     summary="Delete a payment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json([
            'message' => 'Payment deleted successfully'
        ]);
    }
}