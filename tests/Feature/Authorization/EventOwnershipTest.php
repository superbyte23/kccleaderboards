<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('an admin can view, update, and delete any event', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();
    $event = Event::factory()->for($other)->create();

    $this->actingAs($admin);

    expect(Gate::allows('view', $event))->toBeTrue();
    expect(Gate::allows('update', $event))->toBeTrue();
    expect(Gate::allows('delete', $event))->toBeTrue();
});

test('the owner can view, update, and delete their own event', function () {
    $owner = User::factory()->create();
    $event = Event::factory()->for($owner)->create();

    $this->actingAs($owner);

    expect(Gate::allows('view', $event))->toBeTrue();
    expect(Gate::allows('update', $event))->toBeTrue();
    expect(Gate::allows('delete', $event))->toBeTrue();
});

test('a non-owner cannot view, update, or delete another user event', function () {
    $other = User::factory()->create();
    $event = Event::factory()->for($other)->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger);

    expect(Gate::allows('view', $event))->toBeFalse();
    expect(Gate::allows('update', $event))->toBeFalse();
    expect(Gate::allows('delete', $event))->toBeFalse();
});

test('any authenticated user can create an event', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    expect(Gate::allows('create', Event::class))->toBeTrue();
});

test('creating an event assigns the current user as owner', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $event = Event::create([
        'user_id' => $user->id,
        'name' => 'Intramurals 2027',
        'description' => 'Annual collegiate games',
        'event_date' => now()->addMonths(2),
    ]);

    expect($event->user_id)->toBe($user->id);
    $events = Event::where('user_id', $user->id)->count();

    expect($events)->toBe(1);
});