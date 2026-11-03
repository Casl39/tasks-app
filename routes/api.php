<?php

use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Requests\UpdateTaskStatusRequest;

Route::get('/tasks/{id}', [TaskController::class, 'show']);
Route::patch('/tasks/{id}/status', [TaskController::class, 'updateStatus']);
