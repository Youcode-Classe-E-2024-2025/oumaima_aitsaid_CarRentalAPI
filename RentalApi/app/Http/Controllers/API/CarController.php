<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/cars",
     *     summary="Get all cars",
     *     tags={"Cars"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="brand",
     *         in="query",
     *         description="Filter by brand",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="available",
     *         in="query",
     *         description="Filter by availability",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of cars",
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
        $query = Car::query();
        
        // Apply filters
        if ($request->has('brand')) {
            $query->where('brand', 'like', '%' . $request->brand . '%');
        }
        
        if ($request->has('available')) {
            $query->where('is_available', $request->boolean('available'));
        }
        
        // Paginate results
        $cars = $query->paginate(10);
        
        return response()->json($cars);
    }

    /**
     * @OA\Post(
     *     path="/api/cars",
     *     summary="Create a new car",
     *     tags={"Cars"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"brand","model","license_plate","year","color","transmission","fuel_type","seats","daily_rate"},
     *             @OA\Property(property="brand", type="string", example="Toyota"),
     *             @OA\Property(property="model", type="string", example="Corolla"),
     *             @OA\Property(property="license_plate", type="string", example="ABC123"),
     *             @OA\Property(property="year", type="integer", example=2022),
     *             @OA\Property(property="color", type="string", example="White"),
     *             @OA\Property(property="transmission", type="string", enum={"manual", "automatic"}, example="automatic"),
     *             @OA\Property(property="fuel_type", type="string", enum={"gasoline", "diesel", "electric", "hybrid"}, example="gasoline"),
     *             @OA\Property(property="seats", type="integer", example=5),
     *             @OA\Property(property="daily_rate", type="number", format="float", example=50.00),
     *             @OA\Property(property="description", type="string", example="Comfortable sedan for daily use"),
     *             @OA\Property(property="image", type="string", example="https://example.com/car.jpg"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Car created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="car", type="object"),
     *             @OA\Property(property="message", type="string", example="Car created successfully")
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
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'license_plate' => 'required|string|max:255|unique:cars',
            'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'required|string|max:255',
            'transmission' => 'required|in:manual,automatic',
            'fuel_type' => 'required|in:gasoline,diesel,electric,hybrid',
            'seats' => 'required|integer|min:1|max:10',
            'daily_rate' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $car = Car::create($request->all());

        return response()->json([
            'car' => $car,
            'message' => 'Car created successfully',
        ], 201);
    }

    /**
     * @OAGet(
     *     path="/api/cars/{id}",
     *     summary="Get a specific car",
     *     tags={"Cars"},
     *     @OAParameter(
     *         name="id",
     *         in="path",
     *         description="Car ID",
     *         required=true,
     *         @OASchema(type="integer")
     *     ),
     *     @OAResponse(
     *         response=200,
     *         description="Car details",
     *         @OAJsonContent(
     *             @OAProperty(property="car", type="object")
     *         )
     *     ),
     *     @OAResponse(
     *         response=404,
     *         description="Car not found"
     *     )
     * )
     */
    public function show($id)
    {
        $car = Car::findOrFail($id);
        
        return response()->json([
            'car' => $car,
        ]);
    }

    /**
     * @OAPut(
     *     path="/api/cars/{id}",
     *     summary="Update a car",
     *     tags={"Cars"},
     *     security={{"bearerAuth":{}}},
     *     @OAParameter(
     *         name="id",
     *         in="path",
     *         description="Car ID",
     *         required=true,
     *         @OASchema(type="integer")
     *     ),
     *     @OARequestBody(
     *         required=true,
     *         @OAJsonContent(
     *             @OAProperty(property="brand", type="string", example="Toyota"),
     *             @OAProperty(property="model", type="string", example="Corolla"),
     *             @OAProperty(property="license_plate", type="string", example="ABC123"),
     *             @OAProperty(property="year", type="integer", example=2022),
     *             @OAProperty(property="color", type="string", example="White"),
     *             @OAProperty(property="transmission", type="string", enum={"manual", "automatic"}, example="automatic"),
     *             @OAProperty(property="fuel_type", type="string", enum={"gasoline", "diesel", "electric", "hybrid"}, example="gasoline"),
     *             @OAProperty(property="seats", type="integer", example=5),
     *             @OAProperty(property="daily_rate", type="number", format="float", example=50.00),
     *             @OAProperty(property="is_available", type="boolean", example=true),
     *             @OAProperty(property="description", type="string", example="Comfortable sedan for daily use"),
     *             @OAProperty(property="image", type="string", example="https://example.com/car.jpg"),
     *         )
     *     ),
     *     @OAResponse(
     *         response=200,
     *         description="Car updated successfully",
     *         @OAJsonContent(
     *             @OAProperty(property="car", type="object"),
     *             @OAProperty(property="message", type="string", example="Car updated successfully")
     *         )
     *     ),
     *     @OAResponse(
     *         response=404,
     *         description="Car not found"
     *     ),
     *     @OAResponse(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $car = Car::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'brand' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'license_plate' => 'sometimes|required|string|max:255|unique:cars,license_plate,' . $id,
            'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
            'color' => 'sometimes|required|string|max:255',
            'transmission' => 'sometimes|required|in:manual,automatic',
            'fuel_type' => 'sometimes|required|in:gasoline,diesel,electric,hybrid',
            'seats' => 'sometimes|required|integer|min:1|max:10',
            'daily_rate' => 'sometimes|required|numeric|min:0',
            'is_available' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $car->update($request->all());

        return response()->json([
            'car' => $car,
            'message' => 'Car updated successfully',
        ]);
    }

    /**
     * @OADelete(
     *     path="/api/cars/{id}",
     *     summary="Delete a car",
     *     tags={"Cars"},
     *     security={{"bearerAuth":{}}},
     *     @OAParameter(
     *         name="id",
     *         in="path",
     *         description="Car ID",
     *         required=true,
     *         @OASchema(type="integer")
     *     ),
     *     @OAResponse(
     *         response=200,
     *         description="Car deleted successfully",
     *         @OAJsonContent(
     *             @OAProperty(property="message", type="string", example="Car deleted successfully")
     *         )
     *     ),
     *     @OAResponse(
     *         response=404,
     *         description="Car not found"
     *     )
     * )
     */
    public function destroy($id)
    {
        $car = Car::findOrFail($id);
        $car->delete();
        
        return response()->json([
            'message' => 'Car deleted successfully',
        ]);
    }
}