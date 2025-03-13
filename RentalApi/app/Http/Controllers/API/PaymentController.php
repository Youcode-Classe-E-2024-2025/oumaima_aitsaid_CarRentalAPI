<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
public function __constract(){
    Stripe::setApiKey(config('services.stripe.secret'));

}
{
 /**
     * @OA\Post(
     *     path="/payments/create-intent",
     *     summary="Create payment intent with Stripe",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rental_id","amount"},
     *             @OA\Property(property="rental_id", type="integer"),
     *             @OA\Property(property="amount", type="number")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment intent created"
     *     )
     * )
     */
    public function createPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rental_id' => 'required|exists:rentals,id',
            'amount' => 'required|numeric|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rental = Rental::findOrFail($request->rental_id);
        
        // Create Stripe PaymentIntent
        $paymentIntent = PaymentIntent::create([
            'amount' => $request->amount * 100, // Convert to cents
            'currency' => 'usd',
            'metadata' => [
                'rental_id' => $rental->id
            ]
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret
        ]);
    }
     /**
     * @OA\Post(
     *     path="/payments/confirm",
     *     summary="Confirm Stripe payment",
     *     tags={"Payments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_intent_id","rental_id"},
     *             @OA\Property(property="payment_intent_id", type="string"),
     *             @OA\Property(property="rental_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment confirmed"
     *     )
     * )
     */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'rental_id' => 'required|exists:rentals,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
        
        // Create payment record
        $payment = Payment::create([
            'rental_id' => $request->rental_id,
            'amount' => $paymentIntent->amount / 100,
            'payment_method' => 'credit_card',
            'transaction_id' => $paymentIntent->id,
            'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'failed'
        ]);

        // Update rental status if payment successful
        if ($payment->status === 'completed') {
            $rental = Rental::find($request->rental_id);
            $rental->update(['status' => 'active']);
        }

        return response()->json([
            'payment' => $payment,
            'message' => 'Payment processed successfully'
        ]);
    }

}