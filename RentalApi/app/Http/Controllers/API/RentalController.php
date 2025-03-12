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
     *             @OA\Property(property="notes", type="string", example="Business trip rental"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Rental created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="rental", type="object"),
     *             @OA\Property(property="message", type="string", example="Rental created successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'car_id' => 'required|exists:cars,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Check if car is available
        $car = Car::findOrFail($request->car_id);
        if (!$car->is_available) {
            return response()->json([
                'message' => 'Car is not available for rental',
            ], 422);
        }

        // Calculate rental duration and total amount
        $startDate = new \DateTime($request->start_date);
        $endDate = new \DateTime($request->end_date);
        $days = $startDate->diff($endDate)->days;
        $totalAmount = $car->daily_rate * $days;

        // Create rental
        $rental = Rental::create([
            'user_id' => $request->user()->id,
            'car_id' => $request->car_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        // Update car availability
        $car->update(['is_available' => false]);

        return response()->json([
            'rental' => $rental->load(['car', 'user']),
            'message' => 'Rental created successfully',
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
        $rental = Rental::with(['car', 'user', 'payment'])->findOrFail($id);
        
        // Check if user is authorized to view this rental
        if ($rental->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }
        
        return response()->json([
            'rental' => $rental,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/rentals/{id}",
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
        
        // Check if user is authorized to update this rental
        if ($rental->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'status' => 'sometimes|required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If dates are changing, recalculate total amount
        if ($request->has('start_date') || $request->has('end_date')) {
            $startDate = new \DateTime($request->start_date ?? $rental->start_date);
            $endDate = new \DateTime($request->end_date ?? $rental->end_date);
            $days = $startDate->diff($endDate)->days;
            $totalAmount = $rental->car->daily_rate * $days;
            $request->merge(['total_amount' => $totalAmount]);
        }

        // If status is changing to completed or cancelled, make car available again
        if ($request->has('status') && in_array($request->status, ['completed', 'cancelled']) && $rental->status !== 'completed' && $rental->status !== 'cancelled') {
            $rental->car->update(['is_available' => true]);
        }

        $rental->update($request->all());

        return response()->json([
            'rental' => $rental->load(['car', 'user', 'payment']),
            'message' => 'Rental updated successfully',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/rentals/{id}",
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
        
        // Check if user is authorized to delete this rental
        if ($rental->user_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }
        
        // Make car available again
        $rental->car->update(['is_available' => true]);
        
        $rental->delete();
        
        return response()->json([
            'message' => 'Rental deleted successfully',
        ]);
    }
}