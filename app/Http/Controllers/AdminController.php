<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Helper\OfficeHelper;
use App\Models\RealEstate_Location;
use App\Services\AdminService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    use ResponseTrait;

    public function __construct(private AdminService $service) {}

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 12);
        $users = $this->service->getAllAdminUsers($perPage);
        $offices = $users->load('address', 'contact')->map(fn ($user) => OfficeHelper::formatOffice($user));

        return $this->apiResponse('Success', $offices, 200);
    }

    public function setActivation(int $id, Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|boolean',
        ]);

        $result = $this->service->changeActivation($validated['status'], $id);

        return $this->apiResponse(
            'Success',
            $result->load('address', 'contact'),
            200
        );
    }

    public function delete(int $id)
    {

        $this->service->deleteUser($id);

        return $this->apiResponse('User deleted successfully', null, 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'city' => 'required|string|max:255',
            'district' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $location = RealEstate_Location::create([
            'city' => $request->city,
            'district' => $request->district,
        ]);

        return $this->apiResponse('Location created successfully', $location, 201);
    }

    public function destroy($id)
    {
        $location = RealEstate_Location::findOrFail($id);
        if ($location->realEstate()->exists()) {
            throw new ApiException('Cannot delete location with associated real estates', 422);
        }

        $location->delete();

        return $this->apiResponse('Location deleted successfully', null, 200);
    }
}
