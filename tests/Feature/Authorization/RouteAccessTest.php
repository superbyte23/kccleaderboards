<?php

use App\Models\Competition;
use App\Models\Event;
use App\Models\User;

test('guests are redirected to login for protected pages', function () {
    $event = Event::factory()->create();
    $competition = Competition::factory()->for($event)->create();

    foreach (['/dashboard', '/events', '/users', "/event-dashboard/{$event->id}", "/competition-dashboard/{$competition->id}"] as $url) {
        $this->get($url)->assertRedirect(route('login'));
    }
});

test('a non-admin cannot access the users page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/users')->assertForbidden();
});

test('an admin can access the users page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/users')->assertOk();
});

test('a non-admin cannot view another users event dashboard or competition dashboard', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create();
    $competition = Competition::factory()->for($event)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->get("/event-dashboard/{$event->id}")
        ->assertForbidden();

    $this->actingAs($stranger)
        ->get("/competition-dashboard/{$competition->id}")
        ->assertForbidden();
});

test('an owner can access their own event and competition dashboards', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create();
    $competition = Competition::factory()->for($event)->create();

    $this->actingAs($owner)
        ->get("/event-dashboard/{$event->id}")
        ->assertOk();

    $this->actingAs($owner)
        ->get("/competition-dashboard/{$competition->id}")
        ->assertOk();
});

test('an admin can access any event and competition dashboard', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create();
    $competition = Competition::factory()->for($event)->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get("/event-dashboard/{$event->id}")
        ->assertOk();

    $this->actingAs($admin)
        ->get("/competition-dashboard/{$competition->id}")
        ->assertOk();
});

test('public leaderboard is accessible without authentication', function () {
    $event = Event::factory()->create();

    $this->get("/leaderboards/{$event->id}")->assertOk();
});

test('non-admin events page only lists their own events', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Event::factory()->for($owner)->create(['name' => 'Miguel Owned Event']);
    Event::factory()->for($other)->create(['name' => 'Sofia Other Event']);

    $this->actingAs($owner)
        ->get('/events')
        ->assertOk()
        ->assertSee('Miguel Owned Event')
        ->assertDontSee('Sofia Other Event');
});

test('an admin events page lists all events', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->admin()->create();

    Event::factory()->for($owner)->create(['name' => 'Owner Event']);
    Event::factory()->for($other)->create(['name' => 'Other Event']);

    $this->actingAs($admin)
        ->get('/events')
        ->assertOk()
        ->assertSee('Owner Event')
        ->assertSee('Other Event');
});