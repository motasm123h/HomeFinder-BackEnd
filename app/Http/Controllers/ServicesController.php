<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Services_Type;
use Illuminate\Http\Request;
use App\Services\ServiceService;
use App\Services\ServiceTypeService;
use App\Traits\ResponseTrait;
use App\Exceptions\ApiException;

class ServicesController extends Controller
{
    use ResponseTrait;

    public function __construct(
        private ServiceService $service,
        private ServiceTypeService $serviceTypeService
    ) {}

    public function index()
    {
        $services = $this->service->getAllPaginated(10);
        return $this->apiResponse('Services retrieved successfully', $services, 200);
    }

    public function indexType()
    {
        $service_type = Services_Type::all();
        return $this->apiResponse('Service types retrieved', $service_type, 200);
    }

    public function showServiceByType(int $id)
    {
        $service_type = Services_Type::where('id', $id)->first();
        if (!$service_type) {
            throw new ApiException('Service type not found', 404);
        }

        return $this->apiResponse('Service type with services retrieved', $service_type->load('servicesInfo.usersInfo'), 200);
    }

    public function show($id)
    {
        $service = $this->service->findById($id);
        return $this->apiResponse('Service retrieved', $service, 200);
    }

    public function create(Request $request)
    {
        $service = $this->service->create($request->all());
        return $this->apiResponse('Service created successfully', $service, 201);
    }

    public function update(Request $request, $id)
    {
        $service = $this->service->update($id, $request->all());
        return $this->apiResponse('Service updated successfully', $service, 200);
    }

    public function delete($id)
    {
        $this->service->delete($id);
        return $this->apiResponse('Service deleted successfully', null, 200);
    }

    public function createServiceType(Request $request)
    {
        $serviceType = $this->serviceTypeService->create($request->all());
        return $this->apiResponse('Service type created successfully', $serviceType, 201);
    }

    public function deleteServiceType(int $id)
    {
        $this->serviceTypeService->delete($id);
        return $this->apiResponse('Service type deleted successfully', null, 200);
    }

    public function officeService(int $id)
    {
        $user = User::where('id', $id)->with('service')->first();
        if (!$user) {
            throw new ApiException('User not found', 404);
        }

        return $this->apiResponse('Office service retrieved', $user, 200);
    }
}
