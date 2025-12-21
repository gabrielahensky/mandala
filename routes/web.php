<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Dashboard;
use App\Livewire\Ledger;

Route::get('/', Dashboard::class)->name('dashboard');
Route::get('/ledger', Ledger::class)->name('ledger');