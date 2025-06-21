<?php

use Illuminate\Support\Facades\Route;


use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

Route::get('/panorama-image/{path}', function ($path) {
    $fullPath = storage_path("app/public/" . $path);

    if (!File::exists($fullPath)) {
        abort(404);
    }

    return Response::make(File::get($fullPath), 200, [
        'Content-Type' => File::mimeType($fullPath),
        'Access-Control-Allow-Origin' => '*',
    ]);
})->where('path', '.*');

Route::get('/', function () {
    return view('welcome');
});
