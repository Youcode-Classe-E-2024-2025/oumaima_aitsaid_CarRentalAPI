<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
/**
    * @OA\Info(
    *     title="Car Rental API",
    *     version="1.0.0",
    *     description="REST API for Car Rental System",
    *     @OA\Contact(
    *         email="admin@carrental.com",
    *         name="API Support"
    *     ),
    *     @OA\License(
    *         name="MIT",
    *         url="https://opensource.org/licenses/MIT"
    *     )
    * )
    * 
    * @OA\Server(
    *     url="/api",
    *     description="Car Rental API Server"
    * )
    * 
    * @OA\SecurityScheme(
    *     securityScheme="bearerAuth",
    *     type="http",
    *     scheme="bearer",
    *     bearerFormat="JWT"
    * )
    * 
    * @OA\Tag(
    *     name="Authentication",
    *     description="API Endpoints for user authentication"
    * )
    * 
    * @OA\Tag(
    *     name="Cars",
    *     description="API Endpoints for car management"
    * )
    * 
    * @OA\Tag(
    *     name="Rentals",
    *     description="API Endpoints for rental management"
    * )
    * 
    * @OA\Tag(
    *     name="Payments",
    *     description="API Endpoints for payment processing"
    * )
    * 
    */
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}