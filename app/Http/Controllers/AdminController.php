<?php
namespace App\Http\Controllers;

use App\Services\AdminService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use App\Helper\OfficeHelper;
use App\Models\RealEstate_Location;
use Illuminate\Support\Facades\Validator; 

class AdminController extends Controller
{
    use ResponseTrait;
    
    public function __construct(private AdminService $service) {}

    public function index(Request $request)
    {
        try{
            $perPage = $request->input('per_page', 12); 
            $users = $this->service->getAllAdminUsers($perPage);
            $offices = $users->load('address','contact')->map(fn($user) => OfficeHelper::formatOffice($user));
            return $this->apiResponse('Success', $offices, 200);
        }
        catch(\Exception $e){
            \Log::error("Failed to load user realationships : " . $e->getMessage());
            return $this->apiResponse(
                'Error loading user data', 
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    public function setActivation(int $id, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);
    

        try {
            $result = $this->service->changeActivation($validated['status'], $id);
            return $this->apiResponse(
                'Success', 
                $result->load('address', 'contact'), 
                200
            );
        } catch (\Exception $e) {
            return $this->apiResponse(
                'Error: ' . $e->getMessage(), 
                null, 
                400
            );
        }
    }

    public function delete(int $id)
    {
        try {
            $this->service->deleteUser($id);
            return $this->apiResponse('User deleted successfully', null, 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->apiResponse('User not found', null, 404);
            
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error("Database error while deleting user {$id}: " . $e->getMessage());
            return $this->apiResponse('Failed to delete user due to database error', null, 500);
            
        } catch (\Exception $e) {
            \Log::error("Unexpected error while deleting user {$id}: " . $e->getMessage());
            return $this->apiResponse('Failed to delete user', null, 500);
        }
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $location = RealEstate_Location::create([
            'city' => $request->city,
            'district' => $request->district
        ]);

        return response()->json([
            'success' => true,
            'data' => $location
        ], 201);
    }

    public function destroy($id)
    {
        try {
            $location = RealEstate_Location::findOrFail($id);

            if ($location->realEstate()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete location with associated real estates'
                ], 422);
            }

            $location->delete();

            return response()->json([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Location not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete location'
            ], 500);
        }
    }
    
}