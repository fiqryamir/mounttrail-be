<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

class PaymentController extends Controller
{
    private $billplzApiKey;
    private $billplzCollectionId;
    private $billplzBaseUrl;

    public function __construct()
    {
        $this->billplzApiKey = config('services.billplz.api_key');
        $this->billplzCollectionId = config('services.billplz.collection_id');
        $this->billplzBaseUrl = config('services.billplz.base_url', 'https://www.billplz.com/api/v3');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments",
     *     summary="Create payment for booking",
     *     description="Create a Billplz payment bill for a booking",
     *     operationId="createPayment",
     *     tags={"Payments"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Payment creation data",
     *         @OA\JsonContent(
     *             required={"booking_id"},
     *             @OA\Property(property="booking_id", type="integer", example=1, description="Booking ID to make payment for")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Payment created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment", ref="#/components/schemas/Payment"),
     *                 @OA\Property(property="payment_url", type="string", example="https://billplz.com/bills/abc123", description="Billplz payment URL")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment already exists or completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Payment already completed for this booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Not authorized to make payment for this booking",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="You are not authorized to make payment for this booking")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function createPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking = Booking::with(['mount', 'trail', 'guide'])->findOrFail($request->booking_id);

        if (!$booking->isParticipant(Auth::user())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to make payment for this booking'
            ], 403);
        }

        $existingPayment = Payment::where('booking_id', $booking->id)
                                 ->where('user_id', Auth::id())
                                 ->where('status', 'pending')
                                 ->first();

        if ($existingPayment) {
            return response()->json([
                'success' => true,
                'message' => 'Payment already exists',
                'data' => $existingPayment
            ]);
        }

        $paidPayment = Payment::where('booking_id', $booking->id)
                             ->where('user_id', Auth::id())
                             ->where('status', 'paid')
                             ->first();

        if ($paidPayment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment already completed for this booking'
            ], 400);
        }

        try {
            $billplzResponse = $this->createBillplzBill($booking, Auth::user());
            
            if (!$billplzResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment bill'
                ], 500);
            }

            $payment = Payment::create([
                'booking_id' => $booking->id,
                'user_id' => Auth::id(),
                'billplz_bill_id' => $billplzResponse['data']['id'],
                'billplz_url' => $billplzResponse['data']['url'],
                'amount' => $booking->mount->price,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment created successfully',
                'data' => [
                    'payment' => $payment,
                    'payment_url' => $billplzResponse['data']['url']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment'
            ], 500);
        }
    }

    public function getPaymentStatus($paymentId)
    {
        $payment = Payment::where('id', $paymentId)
                         ->where('user_id', Auth::id())
                         ->firstOrFail();

        try {
            $billplzResponse = $this->getBillplzBillStatus($payment->billplz_bill_id);
            
            if ($billplzResponse['success']) {
                $billStatus = $billplzResponse['data']['state'];
                
                if ($billStatus === 'paid' && $payment->status !== 'paid') {
                    $payment->markAsPaid($billplzResponse['data']);
                    
                    $booking = $payment->booking;
                    if ($booking->status === 'pending') {
                        $booking->update(['status' => 'confirmed']);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'data' => $payment->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/webhook",
     *     summary="Billplz webhook callback",
     *     description="Handle payment status updates from Billplz (public endpoint)",
     *     operationId="paymentWebhook",
     *     tags={"Payments"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Billplz webhook data",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="string", example="billplz_abc123", description="Billplz bill ID"),
     *             @OA\Property(property="state", type="string", enum={"paid", "failed"}, example="paid", description="Payment status"),
     *             @OA\Property(property="amount", type="integer", example=25000, description="Amount in cents"),
     *             @OA\Property(property="collection_id", type="string", description="Billplz collection ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Payment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false)
     *         )
     *     )
     * )
     */
    public function webhook(Request $request)
    {
        try {
            $billplzBillId = $request->input('id');
            $status = $request->input('state');
            
            $payment = Payment::where('billplz_bill_id', $billplzBillId)->first();
            
            if (!$payment) {
                Log::warning('Payment not found for Billplz ID: ' . $billplzBillId);
                return response()->json(['success' => false], 404);
            }

            if ($status === 'paid') {
                $payment->markAsPaid($request->all());
                
                $booking = $payment->booking;
                if ($booking->status === 'pending') {
                    $booking->update(['status' => 'confirmed']);
                }
                
                Log::info('Payment marked as paid for booking: ' . $booking->id);
            } elseif ($status === 'failed') {
                $payment->markAsFailed($request->all());
                Log::info('Payment marked as failed for booking: ' . $payment->booking_id);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage());
            return response()->json(['success' => false], 500);
        }
    }

    public function getUserPayments()
    {
        $payments = Payment::where('user_id', Auth::id())
                          ->with(['booking.mount', 'booking.trail', 'booking.guide'])
                          ->orderBy('created_at', 'desc')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    private function createBillplzBill($booking, $user)
    {
        try {
            $response = Http::withBasicAuth($this->billplzApiKey, '')
                          ->post($this->billplzBaseUrl . '/bills', [
                              'collection_id' => $this->billplzCollectionId,
                              'description' => "Mount Trail Booking - {$booking->mount->name} ({$booking->trail->name})",
                              'email' => $user->email,
                              'name' => $user->name,
                              'amount' => $booking->mount->price * 100, // Convert to cents
                              'callback_url' => route('payments.callback'),
                              'redirect_url' => config('app.frontend_url') . '/payment/success',
                          ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('Billplz API error: ' . $response->body());
            return ['success' => false];

        } catch (\Exception $e) {
            Log::error('Billplz API exception: ' . $e->getMessage());
            return ['success' => false];
        }
    }

    private function getBillplzBillStatus($billId)
    {
        try {
            $response = Http::withBasicAuth($this->billplzApiKey, '')
                          ->get($this->billplzBaseUrl . '/bills/' . $billId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return ['success' => false];

        } catch (\Exception $e) {
            Log::error('Billplz status check exception: ' . $e->getMessage());
            return ['success' => false];
        }
    }
}
