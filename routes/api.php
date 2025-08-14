<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommonController;
use App\Http\Controllers\CustomerPreferenceController;
use App\Http\Controllers\NotiController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\RealEstateController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;






Route::get('/user', function () {
    return "hi";
});

Route::get('/sanctum/csrf-cookie', function (Request $request) {
    return response()->json(['message' => 'CSRF cookie set']);
});

Route::prefix('services/')->group(function () {
    Route::get('index', [ServicesController::class, 'index']);
    Route::get('index/{id}', [ServicesController::class, 'show']);
    Route::get('servicesType', [ServicesController::class, 'indexType']);
    Route::get('servicesType/{id}', [ServicesController::class, 'showServiceByType']);
    Route::get('office/{id}', [ServicesController::class, 'officeService']);
});


Route::prefix('search/')->group(function () {
    Route::get('most-search', [SearchController::class, 'mostSearched']);
    Route::get('most-watch', [SearchController::class, 'mostWatch']);
    Route::post('preferences', [SearchController::class, 'preferences']);
    Route::get('/voiceSearch', [SearchController::class, 'search']);
});
Route::prefix('RealEstate/')->group(function () {
    Route::get('getStatus', [RealEstateController::class, 'getStatus']);
    Route::post('index', [RealEstateController::class, 'index']);
    Route::get('getLocation', [RealEstateController::class, 'getLocation']);
    Route::get('getDetails/{id}', [RealEstateController::class, 'getDetails']);
    Route::post('compare', [RealEstateController::class, 'compare']);
});

Route::prefix('complaint/')->group(function () {
    Route::post('create', [CommonController::class, 'create']);
});

Route::prefix('office/')->group(function () {
    Route::post('send-request', [OfficeController::class, 'create']);
});

Route::get('profile/{id}', [AuthController::class, 'profile']);
Route::get('/admin/users', [AdminController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function () {



    Route::get('/customer-preferences', [CustomerPreferenceController::class, 'index']);
    Route::post('/customer-preferences', [CustomerPreferenceController::class, 'store']);
    Route::get('/customer-preferences/{customerPreference}', [CustomerPreferenceController::class, 'show']);
    Route::post('/customer-preferences/{customerPreference}', [CustomerPreferenceController::class, 'update']);
    Route::post('/customer-preferences/delete/{customerPreference}', [CustomerPreferenceController::class, 'destroy']);

    Route::get('/recommendations', [RecommendationController::class, 'getRecommendations']);

    Route::get('getNotifications', [NotiController::class, 'getNotifications']);
    Route::post('deleteNotification/{id}', [NotiController::class, 'deleteNotification']);


    Route::middleware(['activate'])->group(function () {
        Route::prefix('RealEstate/')->group(function () {
            Route::post('create', [RealEstateController::class, 'create']);
            Route::post('update/{id}', [RealEstateController::class, 'update']);
            Route::post('delete/{id}', [RealEstateController::class, 'delete']);
            Route::post('Add360/{id}', [RealEstateController::class, 'Add360']);
        });
        Route::prefix('services/')->group(function () {
            Route::post('create', [ServicesController::class, 'create']);
            Route::post('update/{id}', [ServicesController::class, 'update']);
            Route::post('delete/{id}', [ServicesController::class, 'delete']);
        });
    });


    Route::prefix('office/')->group(function () {
        Route::get('indexRequest', [OfficeController::class, 'getPaginatedRequests']);
        Route::get('indexSent', [OfficeController::class, 'getPaginatedSent']);
        Route::post('delete-request/{id}', [OfficeController::class, 'delete']);
    });


    Route::get('/', [ServicesController::class, 'index']);


    Route::middleware(['admin'])->group(function () {

        Route::prefix('complaint/')->group(function () {
            Route::get('index', [CommonController::class, 'index']);
            Route::get('getReviewsByOffice/{id}', [CommonController::class, 'getReviewsByOffice']);
            Route::post('delete/{id}', [CommonController::class, 'delete']);
            Route::post('seen/{id}', [CommonController::class, 'seen']);
        });
        Route::prefix('admin/')->group(function () {
            Route::post('delete/{id}', [RealEstateController::class, 'delete']);
            Route::post('activate/{id}', [AdminController::class, 'setActivation']);
            Route::post('delete/{id}', [AdminController::class, 'delete']);

            Route::post('serviceTypes/', [ServicesController::class, 'createServiceType']);
            Route::post('serviceTypes/{id}', [ServicesController::class, 'deleteServiceType']);

            Route::post('location', [AdminController::class, 'store']);
            Route::post('location/{id}', [AdminController::class, 'destroy']);

            Route::get('verifications/', [VerificationController::class, 'index']);
            Route::post('verifications/create', [VerificationController::class, 'store']);
            Route::get('verifications/show/{id}', [VerificationController::class, 'show']);
            Route::post('verifications/delete/{id}', [VerificationController::class, 'destroy']);
            Route::post('/registerAdmin', [AuthController::class, 'registerAdmin']);
        });
        Route::post('ver/update/{id}', [VerificationController::class, 'update']);
    });
});

Route::post('/simple-password-reset', [AuthController::class, 'resetPassword']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
