<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Rental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RentalController extends Controller
{
    /**
     * @OA\Get(
     *     path="/rentals",
     *     summary="Get all rentals",
     *     tags={"Rentals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "active", "completed", "cancelled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of rentals",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Rental::with(['user', 'car']);
    
        // Apply status filter if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
    
        // Get authenticated user's rentals
        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        }
    
        return $query->paginate();
    }
/**
 * @OA\Post(
 *     path="/rentals",
 *     summary="Create a new rental",
 *     tags={"Rentals"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"car_id","start_date","end_date"},
 *             @OA\Property(property="car_id", type="integer", example=1),
 *             @OA\Property(property="start_date", type="string", format="date-time", example="2023-03-15T10:00:00Z"),
 *             @OA\Property(property="end_date", type="string", format="date-time", example="2023-03-20T10:00:00Z"),
 *             @OA\Property(property="notes", type="string", example="Business trip rental", nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Rental created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(
 *                 property="rental",
 *                 type="object",
 *                 @OA\Property(property="user_id", type="integer", example=1),
 *                 @OA\Property(property="car_id", type="integer", example=1),
 *                 @OA\Property(property="start_date", type="string", format="date-time"),
 *                 @OA\Property(property="end_date", type="string", format="date-time"),
 *                 @OA\Property(property="total_amount", type="number", format="decimal", example=150.00),
 *                 @OA\Property(property="status", type="string", example="pending"),
 *                 @OA\Property(property="notes", type="string", nullable=true),
 *                 @OA\Property(property="created_at", type="string", format="date-time"),
 *                 @OA\Property(property="updated_at", type="string", format="date-time")
 *             ),
 *             @OA\Property(property="message", type="string", example="Rental created successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=422,
 *         description="Validation error"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     )
 * )
 */
public function store(Request $request)
{
    // First get the authenticated user
    // $user = auth()->user();
    
    // if (!$user) {
    //     return response()->json(['message' => 'Unauthenticated'], 401);
    // }

    $validator = Validator::make($request->all(), [
        'user_id' => 'required|exists:users,id',
        'car_id' => 'required|exists:cars,id',
        'start_date' => 'required|date|after_or_equal:today',
        'end_date' => 'required|date|after:start_date',
        'notes' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Get the car
    $car = Car::findOrFail($request->car_id);

    // Calculate rental duration and total amount
    $startDate = new \DateTime($request->start_date);
    $endDate = new \DateTime($request->end_date);
    $days = $startDate->diff($endDate)->days;
    $totalAmount = $car->daily_rate * $days;

    // Create rental with user_id from authenticated user
    $rental = Rental::create([
        'user_id' => $request->user_id,  // Set the authenticated user's ID
        'car_id' => $request->car_id,
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'total_amount' => $totalAmount,
        'status' => 'pending',
        'notes' => $request->notes,
    ]);

    return response()->json([
        'rental' => $rental->load(['car', 'user']),
        'message' => 'Rental created successfully'
    ], 201);
}
    /**
     * @OA\Get(
     *     path="/rentals/{id}",
     *     summary="Get a specific rental",
     *     tags={"Rentals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Rental ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rental details",
     *         @OA\JsonContent(
     *             @OA\Property(property="rental", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rental not found"
     *     )
     * )
     */
    public function show($id)
    {
        // Check if user is authenticated
        
    
        $rental = Rental::with(['car', 'user', 'payment'])->findOrFail($id);
        
        // Simplified authorization check
        if ($rental->user_id ) {
            return response()->json([
                'rental' => $rental,
            ]);
        }
    
        return response()->json([
            'message' => 'Unauthorized',
        ], 403);
    }
    /**
     * @OA\Put(
     *     path="/rentals/{id}",
     *     summary="Update a rental",
     *     tags={"Rentals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Rental ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="start_date", type="string", format="date-time", example="2023-03-15T10:00:00Z"),
     *             @OA\Property(property="end_date", type="string", format="date-time", example="2023-03-20T10:00:00Z"),
     *             @OA\Property(property="status", type="string", enum={"pending", "active", "completed", "cancelled"}, example="active"),
     *             @OA\Property(property="notes", type="string", example="Business trip rental"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rental updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="rental", type="object"),
     *             @OA\Property(property="message", type="string", example="Rental updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rental not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $rental = Rental::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'status' => 'sometimes|required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Calculate new total amount if dates changed
        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = new \DateTime($request->start_date ?? $rental->start_date);
            $endDate = new \DateTime($request->end_date ?? $rental->end_date);
            $days = $startDate->diff($endDate)->days;
            $totalAmount = $rental->car->daily_rate * $days;
            $request->merge(['total_amount' => $totalAmount]);
        }
    
        $rental->update($request->all());
    
        return response()->json([
            'rental' => $rental->load(['car', 'user', 'payment']),
            'message' => 'Rental updated successfully'
        ]);
    }
    

    /**
     * @OA\Delete(
     *     path="/rentals/{id}",
     *     summary="Delete a rental",
     *     tags={"Rentals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Rental ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rental deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Rental deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Rental not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $rental = Rental::findOrFail($id);
        
       
        $rental->car->update(['is_available' => true]);
        
        $rental->delete();
    
        return response()->json([
            'message' => 'Rental deleted successfully'
        ]);
    }
}