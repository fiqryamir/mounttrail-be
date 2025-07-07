<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');

    // Payment webhook (must be public for Billplz to access)
    Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.callback');

    // Test route
    Route::get('/test', function () {
        return response()->json(['message' => 'API is working']);
    })->name('test');

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Get current user with roles and permissions
        Route::get('/user', function (Request $request) {
            $user = $request->user();
            $user->load('roles', 'permissions');

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ]
            ]);
        })->name('user');

        // Guide specific routes
        Route::middleware('role:guide')->group(function () {
            Route::get('/guide/dashboard', function () {
                return response()->json(['message' => 'Guide dashboard']);
            });

            Route::get('/guide/bookings', function () {
                return response()->json(['message' => 'Guide bookings management']);
            });

            // Add tour management routes here
            // Route::apiResource('tours', TourController::class);
        });

        // User specific routes
        Route::middleware('role:user')->group(function () {
            Route::get('/user/dashboard', function () {
                return response()->json(['message' => 'User dashboard']);
            });

            Route::get('/user/bookings', function () {
                return response()->json(['message' => 'User bookings history']);
            });
        });

        // Booking routes (accessible by authenticated users)
        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index'])->name('bookings.index');
            Route::post('/', [BookingController::class, 'store'])->name('bookings.store');
            Route::get('/{id}', [BookingController::class, 'show'])->name('bookings.show');
            Route::put('/{id}', [BookingController::class, 'update'])->name('bookings.update');
            Route::delete('/{id}', [BookingController::class, 'destroy'])->name('bookings.destroy');
            Route::post('/join', [BookingController::class, 'joinGroup'])->name('bookings.join');
            Route::post('/{id}/leave', [BookingController::class, 'leaveGroup'])->name('bookings.leave');
            Route::get('/search/{groupCode}', [BookingController::class, 'searchByGroupCode'])->name('bookings.search');
        });

        // Payment routes (accessible by authenticated users)
        Route::prefix('payments')->group(function () {
            Route::post('/', [PaymentController::class, 'createPayment'])->name('payments.create');
            Route::get('/{paymentId}/status', [PaymentController::class, 'getPaymentStatus'])->name('payments.status');
            Route::get('/user', [PaymentController::class, 'getUserPayments'])->name('payments.user');
        });

        // Routes accessible by both users and guides
        Route::middleware('role:user|guide')->group(function () {
            Route::get('/tours', function () {
                return response()->json(['message' => 'Available tours list']);
            });

            Route::get('/guides', function () {
                return response()->json(['message' => 'Available guides list']);
            });
        });

        // Admin routes
        Route::middleware('role:admin|super_admin')->group(function () {
            Route::get('/admin/dashboard', function () {
                return response()->json(['message' => 'Admin dashboard']);
            });

            // User management
            Route::get('/admin/users', [RoleController::class, 'getAllUsers']);
            Route::get('/admin/guides', [RoleController::class, 'getAllGuides']);
            Route::get('/admin/stats', [RoleController::class, 'getUserStats']);
            Route::put('/admin/users/{user}/role', [RoleController::class, 'updateUserRole']);
            Route::delete('/admin/users/{user}', [RoleController::class, 'deleteUser']);

            Route::get('/admin/tours', function () {
                return response()->json(['message' => 'All tours management']);
            });

            Route::get('/admin/bookings', function () {
                return response()->json(['message' => 'All bookings management']);
            });
        });

        // Super Admin exclusive routes
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/super-admin/dashboard', function () {
                return response()->json(['message' => 'Super Admin dashboard']);
            });

            Route::get('/super-admin/system', function () {
                return response()->json(['message' => 'System configuration']);
            });

            // Create admin users (only super admin can do this)
            Route::post('/super-admin/create-admin', [RoleController::class, 'createAdmin']);

            Route::get('/super-admin/logs', function () {
                return response()->json(['message' => 'System logs access']);
            });

            Route::post('/super-admin/backup', function () {
                return response()->json(['message' => 'System backup']);
            });
        });

        // Profile management (accessible by all authenticated users)
        Route::put('/profile', function (Request $request) {
            $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
                'password' => 'sometimes|string|min:8|confirmed',
            ]);

            $user = $request->user();

            if ($request->has('name')) {
                $user->name = $request->name;
            }

            if ($request->has('email')) {
                $user->email = $request->email;
            }

            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'message' => 'Profile updated successfully'
            ]);
        });
    });
});
