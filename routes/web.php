<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Ledger;
use App\Livewire\Units;
use App\Livewire\Tenants;
use App\Livewire\UnitDetail;
use App\Livewire\RentUnpaid;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/ledger', Ledger::class)->name('ledger');
Route::get('/units', Units::class)->name('units');
Route::get('/tenants', Tenants::class)->name('tenants');

Route::get('/units/{unit}', UnitDetail::class)
    ->name('units.show');

Route::get('/rent/unpaid', RentUnpaid::class)
    ->name('rent.unpaid');