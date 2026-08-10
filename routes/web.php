<?php

use Platform\Occupational\Livewire\Dashboard;
use Platform\Occupational\Livewire\Employee\Index as EmployeeIndex;
use Platform\Occupational\Livewire\Employee\Show as EmployeeShow;

/*
 * Occupational — Web-Routes (Prefix 'occupational' aus config).
 * Die Betriebe-Verwaltung liegt jetzt im customer-Modul; occupational bleibt
 * die reine Betriebsmedizin-Schicht (Beschäftigte, Vorsorge, Gefährdung).
 */

Route::get('/', Dashboard::class)->name('occupational.dashboard');
Route::get('/employees', EmployeeIndex::class)->name('occupational.employees.index');
Route::get('/employees/{employment}', EmployeeShow::class)->name('occupational.employees.show');
