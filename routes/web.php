<?php

use App\Http\Controllers\IncidentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IncidentController::class, 'index'])->name('incidents.index');

Route::get('/incidents/all', [IncidentController::class, 'getAll'])->name('incidents.all');

Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');

Route::get('/incidents/search', [IncidentController::class, 'search'])->name('incidents.search');

Route::put('/incidents/{incident}/status', [IncidentController::class, 'updateStatus'])->name('incidents.status');