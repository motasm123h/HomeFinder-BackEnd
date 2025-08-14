<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Verification;
use App\Notifications\VerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\ResponseTrait;
use App\Exceptions\ApiException; // Assuming you created ApiException

class VerificationController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        $verifications = Verification::with('usersInfo', 'usersInfo.contact')->paginate(10);
        return $this->apiResponse('Fetched successfully', $verifications, 200);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'national_no' => 'required|string|max:255',
            'identity_no' => 'required|string|max:255',
            'identity_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'contract_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'user_id' => 'required|exists:users,id',
        ]);

        $existingVerification = Verification::where('user_id', $validatedData['user_id'])->first();
        if ($existingVerification) {
            throw new ApiException('Verification already exists for this user', 409, $existingVerification);
        }

        $validatedData['activation'] = 1;
        // dd($validatedData);
        if ($request->hasFile('identity_image')) {
            $validatedData['identity_image'] = $request->file('identity_image')->store('identity_images', 'public');
        }

        if ($request->hasFile('contract_image')) {
            $validatedData['contract_image'] = $request->file('contract_image')->store('contract_images', 'public');
        }

        $verification = Verification::create([
            'national_no' => $validatedData['national_no'],
            'identity_no' => $validatedData['identity_no'],
            'identity_image' => $validatedData['identity_image'],
            'activation' => "1",
            'user_id' => $validatedData['user_id'],
            'contract_image' => $validatedData['contract_image']
        ]);

        $user = User::find($validatedData['user_id']);
        $user->notify(new VerificationNotification($verification));

        return $this->apiResponse('Verification created successfully and notification sent', $verification, 201);
    }

    public function show($id)
    {
        $verification = Verification::with('usersInfo')->findOrFail($id); // ModelNotFoundException handled by Handler
        return $this->apiResponse('Fetched successfully', $verification, 200);
    }

    public function update(Request $request, $id)
    {
        $verification = Verification::findOrFail($id); // ModelNotFoundException handled by Handler

        $validatedData = $request->validate([
            'national_no' => 'sometimes|string|max:255',
            'identity_no' => 'sometimes|string|max:255',
            'identity_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            'activation' => 'sometimes|boolean',
            'user_id' => 'sometimes|exists:users,id',
            'contract_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($request->hasFile('identity_image')) {
            if ($verification->identity_image) {
                Storage::disk('public')->delete($verification->identity_image);
            }
            $validatedData['identity_image'] = $request->file('identity_image')->store('identity_images', 'public');
        }

        if ($request->hasFile('contract_image')) {
            if ($verification->contract_image) {
                Storage::disk('public')->delete($verification->contract_image);
            }
            $validatedData['contract_image'] = $request->file('contract_image')->store('contract_images', 'public');
        }

        $verification->update($validatedData);
        return $this->apiResponse('Verification updated successfully', $verification, 200);
    }

    public function destroy($id)
    {
        $verification = Verification::findOrFail($id);

        if ($verification->identity_image) {
            Storage::disk('public')->delete($verification->identity_image);
        }
        if ($verification->contract_image) {
            Storage::disk('public')->delete($verification->contract_image);
        }

        $verification->delete();
        return $this->apiResponse('Verification deleted successfully', null, 200);
    }
}
