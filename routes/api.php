<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProgrammingLanguageController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('/sessions/bootstrap', [SessionController::class, 'bootstrap']);
Route::post('/projects/init', [ProjectController::class, 'init']);

Route::get('/files/tree', [FileController::class, 'tree']);
Route::get('/files/read', [FileController::class, 'read']);
Route::post('/files', [FileController::class, 'create']);
Route::put('/files', [FileController::class, 'update']);
Route::delete('/files', [FileController::class, 'delete']);
Route::get('/languages', [ProgrammingLanguageController::class, 'index']);

Route::post('/execute/stream', [ExecuteCodeController::class, 'stream']);

