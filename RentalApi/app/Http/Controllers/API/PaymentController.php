<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    /**
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment operations with Stripe integration"
 * )
 */
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
/**
 * @OA\Post(
 *     path="/payments/create-intent",
 *     summary="Create a new payment intent",
 *     tags={"Payments"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"rental_id", "amount", "payment_method"},
 *             @OA\Property(property="rental_id", type="integer", example=1),
 *             @OA\Property(property="amount", type="number", format="float", example=275.00),
 *             @OA\Property(property="payment_method", type="string", enum={"credit_card", "debit_card", "paypal", "cash"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment intent created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="clientSecret", type="string")
 *         )
 *     )
 * )
 */


    public function createPaymentIntent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rental_id' => 'required|exists:rentals,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,cash'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $rental = Rental::findOrFail($request->rental_id);
        
        $paymentIntent = PaymentIntent::create([
            'amount' => $request->amount * 100,
            'currency' => 'usd',
            'payment_method_types' => ['card'],
            'metadata' => [
                'rental_id' => $rental->id,
                'payment_method' => $request->payment_method
            ]
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret
        ]);
    }
/**
 * @OA\Post(
 *     path="/payments/confirm",
 *     summary="Confirm payment after intent",
 *     tags={"Payments"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"payment_intent_id", "rental_id", "payment_method"},
 *             @OA\Property(property="payment_intent_id", type="string", example="pi_3OqXyZKuReXqKZZp1gYQHhN5"),
 *             @OA\Property(property="rental_id", type="integer", example=1),
 *             @OA\Property(property="payment_method", type="string", enum={"credit_card", "debit_card", "paypal", "cash"})
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment confirmed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="payment", type="object"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     )
 * )
 */
    public function confirmPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => 'required|string',
            'rental_id' => 'required|exists:rentals,id',
            'payment_method' => 'required|in:credit_card,debit_card,paypal,cash'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);
        
        $payment = Payment::create([
            'rental_id' => $request->rental_id,
            'amount' => $paymentIntent->amount / 100,
            'payment_method' => $request->payment_method,
            'transaction_id' => $paymentIntent->id,
            'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'failed'
        ]);

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