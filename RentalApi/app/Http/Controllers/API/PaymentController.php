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
}