<?php
namespace App\Http\Controllers;

use App\Services\AdminService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    use ResponseTrait;
    
    public function __construct(private AdminService $service) {}

    public function index(Request $request)
    {
        try{
            $perPage = $request->input('per_page', 15); 
            $users = $this->service->getAllAdminUsers($perPage);
            return $this->apiResponse('Success', $users->load('realEstate','address','service'), 200);
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

    //no test
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
}