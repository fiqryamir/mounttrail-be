<?php

namespace App\Http\Controllers;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Mount Trail Booking API",
 *     description="API for booking mount expedition trails with group functionality and payment processing",
 *     @OA\Contact(
 *         email="admin@mounttrail.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * @OA\Server(
 *     url="https://mounttrail-backend.test",
 *     description="Mount Trail API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter token in format (Bearer <token>)"
 * )
 * @OA\PathItem(path="/api/v1")
 */
class SwaggerController extends Controller
{
    public function generateDocs()
    {
        $docsPath = storage_path('api-docs/api-docs.json');
        
        if (file_exists($docsPath)) {
            $docs = json_decode(file_get_contents($docsPath), true);
            return response()->json($docs);
        }
        
        return response()->json([
            'error' => 'API documentation not found'
        ], 404);
    }

    public function serveDocs()
    {
        return view('swagger.index');
    }
}
