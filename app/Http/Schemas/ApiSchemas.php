<?php

namespace App\Http\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="User model",
 *     @OA\Property(property="id", type="integer", example=1, description="User ID"),
 *     @OA\Property(property="name", type="string", example="John Doe", description="User's full name"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com", description="User's email address"),
 *     @OA\Property(property="phone", type="string", example="+1234567890", description="User's phone number (for guides)"),
 *     @OA\Property(property="bio", type="string", example="Experienced mountain guide", description="User's biography (for guides)"),
 *     @OA\Property(property="experience_years", type="integer", example=5, description="Years of experience (for guides)"),
 *     @OA\Property(property="certifications", type="array", @OA\Items(type="string"), example={"First Aid", "Mountain Safety"}, description="List of certifications (for guides)"),
 *     @OA\Property(property="specialties", type="array", @OA\Items(type="string"), example={"Rock Climbing", "Alpine"}, description="List of specialties (for guides)"),
 *     @OA\Property(property="rating", type="number", format="float", example=4.5, description="Guide rating (for guides)"),
 *     @OA\Property(property="is_available", type="boolean", example=true, description="Guide availability status (for guides)"),
 *     @OA\Property(property="roles", type="array", @OA\Items(type="string"), example={"user"}, description="User roles"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string"), example={"make_booking"}, description="User permissions"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Last update timestamp")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Mount",
 *     type="object",
 *     title="Mount",
 *     description="Mountain model",
 *     @OA\Property(property="id", type="integer", example=1, description="Mount ID"),
 *     @OA\Property(property="name", type="string", example="Mount Kinabalu", description="Mountain name"),
 *     @OA\Property(property="description", type="string", example="Highest peak in Malaysia", description="Mountain description"),
 *     @OA\Property(property="price", type="number", format="float", example=250.00, description="Base price for booking"),
 *     @OA\Property(property="max_participants", type="integer", example=20, description="Maximum participants per booking"),
 *     @OA\Property(property="location", type="string", example="Sabah, Malaysia", description="Mountain location"),
 *     @OA\Property(property="altitude", type="number", format="float", example=4095.20, description="Mountain altitude in meters"),
 *     @OA\Property(property="duration_days", type="integer", example=2, description="Duration of expedition in days"),
 *     @OA\Property(property="images", type="array", @OA\Items(type="string"), example={"image1.jpg", "image2.jpg"}, description="List of image URLs"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether the mount is available for booking"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Last update timestamp")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Trail",
 *     type="object",
 *     title="Trail",
 *     description="Trail model",
 *     @OA\Property(property="id", type="integer", example=1, description="Trail ID"),
 *     @OA\Property(property="mount_id", type="integer", example=1, description="Mount ID this trail belongs to"),
 *     @OA\Property(property="name", type="string", example="Summit Trail", description="Trail name"),
 *     @OA\Property(property="description", type="string", example="Direct route to summit", description="Trail description"),
 *     @OA\Property(property="difficulty_level", type="string", enum={"easy", "moderate", "hard", "extreme"}, example="moderate", description="Trail difficulty level"),
 *     @OA\Property(property="distance_km", type="number", format="float", example=8.5, description="Trail distance in kilometers"),
 *     @OA\Property(property="estimated_hours", type="integer", example=6, description="Estimated completion time in hours"),
 *     @OA\Property(property="waypoints", type="array", @OA\Items(type="object"), description="List of GPS waypoints"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Whether the trail is available"),
 *     @OA\Property(property="mount", ref="#/components/schemas/Mount", description="Mount details"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Last update timestamp")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Booking",
 *     type="object",
 *     title="Booking",
 *     description="Booking model",
 *     @OA\Property(property="id", type="integer", example=1, description="Booking ID"),
 *     @OA\Property(property="group_code", type="string", example="ABCD-EFGH", description="Unique group code for joining"),
 *     @OA\Property(property="mount_id", type="integer", example=1, description="Mount ID"),
 *     @OA\Property(property="trail_id", type="integer", example=1, description="Trail ID"),
 *     @OA\Property(property="guide_id", type="integer", example=2, description="Guide user ID"),
 *     @OA\Property(property="created_by", type="integer", example=1, description="User ID who created the booking"),
 *     @OA\Property(property="booking_date", type="string", format="date", example="2023-12-01", description="Date of the expedition"),
 *     @OA\Property(property="start_time", type="string", format="time", example="06:00", description="Start time of the expedition"),
 *     @OA\Property(property="max_participants", type="integer", example=10, description="Maximum participants for this booking"),
 *     @OA\Property(property="current_participants", type="integer", example=3, description="Current number of participants"),
 *     @OA\Property(property="total_price", type="number", format="float", example=250.00, description="Total booking price"),
 *     @OA\Property(property="status", type="string", enum={"pending", "confirmed", "cancelled", "completed"}, example="pending", description="Booking status"),
 *     @OA\Property(property="notes", type="string", example="Please bring warm clothes", description="Additional notes"),
 *     @OA\Property(property="mount", ref="#/components/schemas/Mount", description="Mount details"),
 *     @OA\Property(property="trail", ref="#/components/schemas/Trail", description="Trail details"),
 *     @OA\Property(property="guide", ref="#/components/schemas/User", description="Guide details"),
 *     @OA\Property(property="users", type="array", @OA\Items(ref="#/components/schemas/User"), description="List of participants"),
 *     @OA\Property(property="payments", type="array", @OA\Items(ref="#/components/schemas/Payment"), description="List of payments"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Last update timestamp")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Payment",
 *     type="object",
 *     title="Payment",
 *     description="Payment model",
 *     @OA\Property(property="id", type="integer", example=1, description="Payment ID"),
 *     @OA\Property(property="booking_id", type="integer", example=1, description="Booking ID"),
 *     @OA\Property(property="user_id", type="integer", example=1, description="User ID who made the payment"),
 *     @OA\Property(property="billplz_bill_id", type="string", example="billplz_abc123", description="Billplz bill ID"),
 *     @OA\Property(property="billplz_url", type="string", example="https://billplz.com/bills/abc123", description="Billplz payment URL"),
 *     @OA\Property(property="amount", type="number", format="float", example=250.00, description="Payment amount"),
 *     @OA\Property(property="status", type="string", enum={"pending", "paid", "failed", "cancelled"}, example="pending", description="Payment status"),
 *     @OA\Property(property="payment_method", type="string", example="online_banking", description="Payment method used"),
 *     @OA\Property(property="paid_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Payment completion timestamp"),
 *     @OA\Property(property="billplz_response", type="object", description="Billplz response data"),
 *     @OA\Property(property="booking", ref="#/components/schemas/Booking", description="Booking details"),
 *     @OA\Property(property="user", ref="#/components/schemas/User", description="User details"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Creation timestamp"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", description="Last update timestamp")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     title="API Response",
 *     description="Standard API response format",
 *     @OA\Property(property="success", type="boolean", example=true, description="Whether the request was successful"),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully", description="Response message"),
 *     @OA\Property(property="data", type="object", description="Response data"),
 *     @OA\Property(property="errors", type="object", description="Validation errors (if any)")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     title="Validation Error",
 *     description="Validation error response",
 *     @OA\Property(property="success", type="boolean", example=false, description="Always false for errors"),
 *     @OA\Property(property="message", type="string", example="Validation failed", description="Error message"),
 *     @OA\Property(property="errors", type="object", example={"email": {"The email field is required."}}, description="Validation errors by field")
 * )
 */

class ApiSchemas
{
    // This class is only used for organizing OpenAPI schemas
}