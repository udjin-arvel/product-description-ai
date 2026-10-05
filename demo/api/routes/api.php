<?php

declare(strict_types=1);

use App\Http\Controllers\GenerateController;
use Illuminate\Support\Facades\Route;

Route::post('/generate', GenerateController::class);
