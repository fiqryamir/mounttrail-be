<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Mount;
use App\Models\Trail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class BookingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/bookings",
     *     summary="Get user's bookings",
     *     description="Retrieve all bookings for the authenticated user",
     *     operationId="getUserBookings",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User bookings retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Booking"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function index()
    {
        $bookings = Auth::user()->bookings()->with(['mount', 'trail', 'guide'])->get();
        
        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/bookings",
     *     summary="Create a new booking",
     *     description="Create a new mount expedition booking with automatic group code generation",
     *     operationId="createBooking",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Booking creation data",
     *         @OA\JsonContent(
     *             required={"mount_id","trail_id","guide_id","booking_date","start_time","max_participants"},
     *             @OA\Property(property="mount_id", type="integer", example=1, description="Mount ID"),
     *             @OA\Property(property="trail_id", type="integer", example=1, description="Trail ID"),
     *             @OA\Property(property="guide_id", type="integer", example=2, description="Guide user ID"),
     *             @OA\Property(property="booking_date", type="string", format="date", example="2023-12-01", description="Expedition date (must be in the future)"),
     *             @OA\Property(property="start_time", type="string", format="time", example="06:00", description="Start time (HH:MM format)"),
     *             @OA\Property(property="max_participants", type="integer", minimum=1, maximum=20, example=10, description="Maximum participants for this booking"),
     *             @OA\Property(property="notes", type="string", example="Please bring warm clothes", description="Additional notes (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Booking created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Booking"),
     *             @OA\Property(property="group_code", type="string", example="ABCD-EFGH", description="Generated group code for others to join")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Guide not available or other business logic error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Guide is not available on the selected date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to create booking")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mount_id' => 'required|exists:mounts,id',
            'trail_id' => 'required|exists:trails,id',
            'guide_id' => 'required|exists:users,id',
            'booking_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
            'max_participants' => 'required|integer|min:1|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $mount = Mount::findOrFail($request->mount_id);
        $trail = Trail::findOrFail($request->trail_id);
        $guide = User::findOrFail($request->guide_id);

        if (!$guide->hasRole('guide')) {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not a guide'
            ], 400);
        }

        if (!$guide->isAvailableOn($request->booking_date)) {
            return response()->json([
                'success' => false,
                'message' => 'Guide is not available on the selected date'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'mount_id' => $request->mount_id,
                'trail_id' => $request->trail_id,
                'guide_id' => $request->guide_id,
                'created_by' => Auth::id(),
                'booking_date' => $request->booking_date,
                'start_time' => $request->start_time,
                'max_participants' => $request->max_participants,
                'current_participants' => 1,
                'total_price' => $mount->price,
                'notes' => $request->notes,
            ]);

            $booking->users()->attach(Auth::id(), [
                'is_creator' => true,
                'status' => 'confirmed',
                'joined_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => $booking->load(['mount', 'trail', 'guide']),
                'group_code' => $booking->group_code
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking'
            ], 500);
        }
    }

    public function show($id)
    {
        $booking = Booking::with(['mount', 'trail', 'guide', 'users', 'payments'])
                          ->findOrFail($id);

        if (!$booking->isParticipant(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this booking'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/bookings/join",
     *     summary="Join a booking group",
     *     description="Join an existing booking group using the group code",
     *     operationId="joinBookingGroup",
     *     tags={"Bookings"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Group code to join",
     *         @OA\JsonContent(
     *             required={"group_code"},
     *             @OA\Property(property="group_code", type="string", pattern="^[A-Z]{4}-[A-Z]{4}$", example="ABCD-EFGH", description="Group code in format XXXX-XXXX")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successfully joined the group",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Successfully joined the group"),
     *             @OA\Property(property="data", ref="#/components/schemas/Booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot join group (already member, group full, etc.)",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You are already part of this group")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Invalid group code",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid group code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function joinGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_code' => 'required|string|size:9|regex:/^[A-Z]{4}-[A-Z]{4}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid group code format',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking = Booking::where('group_code', $request->group_code)->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid group code'
            ], 404);
        }

        if ($booking->isParticipant(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are already part of this group'
            ], 400);
        }

        if (!$booking->canAcceptMoreParticipants()) {
            return response()->json([
                'success' => false,
                'message' => 'This group is already full'
            ], 400);
        }

        if ($booking->addParticipant(Auth::user())) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully joined the group',
                'data' => $booking->load(['mount', 'trail', 'guide'])
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to join group'
        ], 500);
    }

    public function leaveGroup($id)
    {
        $booking = Booking::findOrFail($id);

        if (!$booking->isParticipant(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not part of this group'
            ], 400);
        }

        if ($booking->removeParticipant(Auth::user())) {
            return response()->json([
                'success' => true,
                'message' => 'Successfully left the group'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cannot leave group as you are the creator'
        ], 400);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this booking'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'booking_date' => 'sometimes|date|after:today',
            'start_time' => 'sometimes|date_format:H:i',
            'max_participants' => 'sometimes|integer|min:' . $booking->current_participants,
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking->update($request->only(['booking_date', 'start_time', 'max_participants', 'notes']));

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully',
            'data' => $booking->load(['mount', 'trail', 'guide'])
        ]);
    }

    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->created_by !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to cancel this booking'
            ], 403);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully'
        ]);
    }

    public function searchByGroupCode($groupCode)
    {
        $booking = Booking::where('group_code', $groupCode)
                          ->with(['mount', 'trail', 'guide'])
                          ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'No booking found with this group code'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }
}
