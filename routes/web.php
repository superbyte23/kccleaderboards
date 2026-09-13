<?php

use App\Models\Event;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Events created on this platform — the only live data the landing page shows
    $events = Event::latest()->withCount('teams', 'competitions')->get();

    return view('welcome', [
        'events' => $events,
        'totals' => [
            'events' => $events->count(),
            'teams' => $events->sum('teams_count'),
            'competitions' => $events->sum('competitions_count'),
        ],
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
    Route::livewire("/users", "pages::users")->name('users');
    
});

Route::view('/leaderboards/{event}', "leaderboard")->name('leaderboards');

require __DIR__.'/settings.php';
