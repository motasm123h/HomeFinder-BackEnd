<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Verification;
use App\Notifications\VerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    public function index()
    {
        $verifications = Verification::with('usersInfo','usersInfo.contact')->paginate(10);
        return response()->json($verifications);
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
            return response()->json([
                'message' => 'Verification already exists for this user',
                'verification' => $existingVerification
            ], 409); 
        }

        $validatedData['activation'] = 1;

        if ($request->hasFile('identity_image')) {
            $validatedData['identity_image'] = $request->file('identity_image')->store('identity_images', 'public');
        }

        if ($request->hasFile('contract_image')) {
            $validatedData['contract_image'] = $request->file('contract_image')->store('contract_images', 'public');
        }

        $verification = Verification::create($validatedData);

        $user = User::find($validatedData['user_id']);
        $user->notify(new VerificationNotification($verification));
        
        return response()->json([
            'message' => 'Verification created successfully and notification sent',
            'verification' => $verification
        ], 201);
    }

    public function show($id)
    {
        $verification = Verification::with('usersInfo')->findOrFail($id);
        return response()->json($verification);
    }

    public function update(Request $request, $id)
    {
        $verification = Verification::findOrFail($id);

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
        return response()->json($verification);
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
        return response()->json(['message' => 'Verification deleted successfully']);
    }
}