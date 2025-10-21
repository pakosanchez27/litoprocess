<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentosController;
use App\Http\Controllers\RedactorIAController;
use App\Http\Controllers\ProcedimientoController;

Route::get('/', function () {
  return view('dashboard');
});


Route::get('/create-procedimiento', [ProcedimientoController::class, 'create'])->name('procedimientos.create');
Route::post('/store-procedimiento', [ProcedimientoController::class, 'store'])->name('procedimientos.store');
Route::get('/procedimiento/upload', [ProcedimientoController::class, 'uploadform'])->name('procedimientos.uploadform');



// Redactor
Route::post('/ia/redactar', [RedactorIAController::class, 'stream'])->name('ia.redactar');
Route::post('/ia/diagrama', [RedactorIAController::class, 'diagrama'])->name('ia.diagrama');