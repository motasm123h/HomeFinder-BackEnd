<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\ServiceService;
use App\Services\ServiceTypeService;


class ServicesController extends Controller
{
    
  public function __construct(
        private ServiceService $service,
        private ServiceTypeService $serviceTypeService
    ) {}

    
    public function index()
    {
        try {
            $services = $this->service->getAllPaginated();
            
            return response()->json([
                'success' => true,
                'data' => $services
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function show($id)
    {
        try {
            $service = $this->service->findById($id);
            
            return response()->json([
                'success' => true,
                'data' => $service
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 404);
        }
    }

    public function create(Request $request)
    {
        try {
            $service = $this->service->create($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $service
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $service = $this->service->update($id, $request->all());
            
            return response()->json([
                'success' => true,
                'data' => $service
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function delete($id)
    {
        try {
            $this->service->delete($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function createServiceType(Request $request)
    {
        try {
            $serviceType = $this->serviceTypeService->create($request->all());
            
            return response()->json([
                'success' => true,
                'data' => $serviceType
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }

    public function deleteServiceType(int $id)
    {
        try {
            $id = (int)$id; 
            $this->serviceTypeService->delete($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Service type deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode() ?: 500);
        }
    }


    public function officeService(int $id){
        $user = User::where('id',$id)->with('service')->first();
        return response()->json([
            'data' => $user,
        ]);
    }
}