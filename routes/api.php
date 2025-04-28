<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OfficeController;
use App\Http\Controllers\RealEstateController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ServicesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;







Route::get('/user', function () {
    return "hi";
});

// Route::get('/sanctum/csrf-cookie', function (Request $request) {
//     return response()->json(['message' => 'CSRF cookie set']);
// });



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/users', [AdminController::class, 'index']);
    //not tested
    Route::get('profile/{id}',[AuthController::class,'profile']);
    

    Route::prefix('RealEstate/')->group(function(){
        Route::post('create',[RealEstateController::class,'create']);
        Route::post('index',[RealEstateController::class,'index']);
        Route::post('update/{id}',[RealEstateController::class,'update']);
        Route::post('delete/{id}',[RealEstateController::class,'delete']);

        Route::get('getDetails/{id}',[RealEstateController::class,'getDetails']);
        
    });
    
    Route::prefix('office/')->group(function(){
        Route::get('indexRequest',[OfficeController::class,'getPaginatedRequests']);
        Route::get('indexSent',[OfficeController::class,'getPaginatedSent']);
        Route::post('send-request',[OfficeController::class,'create']);
        Route::post('delete-request/{id}',[OfficeController::class,'delete']);
        // Route::get('getDetails/{id}',[RealEstateController::class,'getDetails']);
    });

    Route::prefix('services/')->group(function(){
        Route::post('index',[ServicesController::class,'index']);
        Route::post('create',[ServicesController::class,'create']);
        Route::post('update',[ServicesController::class,'update']);
        Route::post('delete',[ServicesController::class,'delete']);
    });

    Route::prefix('search/')->group(function(){
        Route::get('most-search',[SearchController::class,'mostSearched']);
        Route::get('most-watch',[SearchController::class,'mostWatch']);
        Route::post('preferences',[SearchController::class,'preferences']);
        // Route::post('delete',[SearchController::class,'delete']);
    });

    Route::prefix('complaint/')->group(function(){
        Route::post('create',[ServicesController::class,'create']);
        Route::post('delete/{id}',[ServicesController::class,'destroy']);
        Route::get('index',[ServicesController::class,'index']);
    });
    Route::prefix('admin/')->group(function(){
        Route::post('activate/{id}',[AdminController::class,'setActivation']);
        Route::post('delete/{id}',[AdminController::class,'delete']);
    });

    
});

Route::get('/test',function(){
    return 'hi';
});
Route::post('/simple-password-reset', [AuthController::class, 'resetPassword']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);




