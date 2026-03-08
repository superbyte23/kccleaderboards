<?php

use App\Models\Event;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        // We use get() to pass a collection of all active events
        'events' => Event::latest()->get() 
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::livewire("/events", "pages::events")->name("events");
    // Route::livewire("/competitions", "pages::competitions")->name("competitions");
    // Route::livewire("/teams", "pages::teams")->name("teams");

    Route::livewire("/event-dashboard/{event}", "pages::event-dashboard")->name("event-dashboard");
    Route::livewire("/competition-dashboard/{competition}", "pages::competition-dashboard")->name("competition-dashboard");
    Route::livewire("/test-toast", "pages::example-sileo-toaster");
    
});

Route::view('/leaderboards/{event}', "leaderboard")->name('leaderboards');

require __DIR__.'/settings.php';
