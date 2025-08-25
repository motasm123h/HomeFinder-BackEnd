<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Helper\ProfileHelper;
use App\Helper\RealEstateHelper;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateRequest;
use App\Models\User;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ResponseTrait;

    public function register(RegisterRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated(); // Validation handled by Form Request

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
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
    }

    public function registerAdmin(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validate([
                'name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 1,
            ]);

            return $this->apiResponse(
                'Registration successful',
                $user->append('token'),
                201
            );
        });
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (! Auth::attempt($credentials)) {
            throw new ApiException('Invalid credentials', 401);
        }

        return $this->apiResponse(
            'Login successful',
            auth()->user()->append('token'),
            200
        );
    }

    public function logout()
    {
        optional(auth()->user())->tokens()->delete();

        return $this->apiResponse('Logged out successfully', null, 200);
    }

    public function profile(int $id)
    {

        $user = User::with(['address', 'contact'])->findOrFail($id);
        $realEstates = $user->realEstate()
            ->with(['images' => fn ($q) => $q->limit(1), 'properties'])
            ->paginate(10);

        $services = $user->service()->paginate(10);
        $verification = $user->verification()->get();
        $formattedData = ProfileHelper::formatUserProfile($user);
        $formattedData['realEstate'] = array_map(
            fn ($item) => RealEstateHelper::formatRealEstate($item),
            $realEstates->items()
        );
        $formattedData['verification'] = $verification;
        $formattedData['service'] = $services->items();

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'data' => $formattedData,
            'meta' => [
                'realEstate' => $this->buildPaginationMeta($realEstates),
                'service' => $this->buildPaginationMeta($services),
            ],
            'links' => [
                'realEstate' => $this->buildPaginationLinks($realEstates),
                'service' => $this->buildPaginationLinks($services),
            ],
        ]);
    }

    public function update(UpdateRequest $request, User $user)
    {
        return DB::transaction(function () use ($request, $user) {
            $validated = $request->validated();
            $user->update([
                'name' => $validated['name'] ?? $user->name,
                'email' => $validated['email'] ?? $user->email,
                'password' => isset($validated['password']) ? Hash::make($validated['password']) : $user->password,
            ]);

            $user->address()->update([
                'city' => $validated['city'] ?? $user->address->city,
                'district' => $validated['district'] ?? $user->address->district,
            ]);

            $user->contact()->update([
                'phone_no' => $validated['phone_no'] ?? $user->contact->phone_no,
                // 'username' => $validated['username'] ?? $user->contact->username,
            ]);

            return $this->apiResponse(
                'User updated successfully',
                $user,
                200
            );
        });
    }

    private function buildPaginationMeta($paginator)
    {
        return [
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function buildPaginationLinks($paginator)
    {
        return [
            'first_page_url' => $paginator->url(1),
            'last_page_url' => $paginator->url($paginator->lastPage()),
            'next_page_url' => $paginator->nextPageUrl(),
            'prev_page_url' => $paginator->previousPageUrl(),
        ];
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        User::where('email', $validated['email'])
            ->update(['password' => Hash::make($validated['password'])]);

        return $this->apiResponse(
            'Password updated successfully',
            null,
            200
        );
    }
}
