<?php

use App\Http\Controllers\SwaggerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/api/documentation', [SwaggerController::class, 'serveDocs'])->name('swagger.ui');
Route::get('/api/docs', [SwaggerController::class, 'generateDocs'])->name('swagger.docs');
