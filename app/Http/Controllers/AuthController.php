<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Helper\ProfileHelper;

class AuthController extends Controller
{
    use ResponseTrait;

    public function register(RegisterRequest $request)
    {   
        
        try {
            return DB::transaction(function () use ($request) {
                $validated = $request->validated();
                
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);
    
                $user->address()->create([
                    'city' => $validated['city'],
                    'district' => $validated['district'],
                ]);
    
                $user->contact()->create([
                    'phone_no' => $validated['phone_no'],
                    'username' => $validated['username'],
                ]);
    
                return $this->apiResponse(
                    'Registration successful',
                    $user->append('token'),
                    201
                );
            });
        } catch (\Exception $e) {
            \Log::error("Registration failed: " . $e->getMessage());
            return $this->apiResponse(
                'Registration failed',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->validated();
    
            if (!Auth::attempt($credentials)) {
                return $this->apiResponse('Invalid credentials', null, 401);
            }
    
            return $this->apiResponse(
                'Login successful',
                auth()->user()->append('token'),
                200
            );
        } catch (\Exception $e) {
            \Log::error("Login failed: " . $e->getMessage());
            return $this->apiResponse(
                'Login failed',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function logout()
    {
        try {
            optional(auth()->user())->tokens()->delete();
            return $this->apiResponse('Logged out successfully', null, 200);
        } catch (\Exception $e) {
            \Log::error("Logout failed: " . $e->getMessage());
            return $this->apiResponse(
                'Logout failed',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

   public function profile(int $id)
    {
        try {
            $user = User::with([
                'address', 
                'contact',
                'realEstate' => function($query) {
                    $query->limit(10);
                },
                'service' => function($query) {
                    $query->limit(10);
                }
            ])->findOrFail($id);

            $formattedData = ProfileHelper::formatUserProfile($user);

            return response()->json([
                'message' => 'Profile retrieved successfully',
                'data' => $formattedData,
                'meta' => [
                    'realEstate' => [
                        'total' => $user->realEstate()->count(),
                        'remaining' => max(0, $user->realEstate()->count() - 10)
                    ],
                    'service' => [
                        'total' => $user->service()->count(),
                        'remaining' => max(0, $user->service()->count() - 10)
                    ]
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            \Log::error("Profile retrieval error: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function resetPassword(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email',
                'password' => 'required|min:8|confirmed'
            ]);
    
            if ($validator->fails()) {
                return $this->apiResponse(
                    'Validation failed',
                    ['errors' => $validator->errors()],
                    422
                );
            }
    
            $validated = $validator->validated();
            
            User::where('email', $validated['email'])
                ->update(['password' => Hash::make($validated['password'])]);
    
            return $this->apiResponse(
                'Password updated successfully',
                null,
                200
            );
        } catch (\Exception $e) {
            \Log::error("Password reset failed: " . $e->getMessage());
            return $this->apiResponse(
                'Password reset failed',
                ['error' => $e->getMessage()],
                500
            );
        }
    }
}