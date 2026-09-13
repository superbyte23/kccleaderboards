<?php

use App\Models\Competition;
use App\Models\Event;
use App\Models\Result;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('team, competition, and result policies delegate to the owning event', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $event = Event::factory()->for($owner)->create();
    $team = Team::factory()->for($event)->create();
    $competition = Competition::factory()->for($event)->create();
    $result = Result::factory()->for($team)->for($competition)->create();

    $this->actingAs($owner);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::allows($ability, $team))->toBeTrue();
        expect(Gate::allows($ability, $competition))->toBeTrue();
        expect(Gate::allows($ability, $result))->toBeTrue();
    }

    $this->actingAs($stranger);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::allows($ability, $team))->toBeFalse();
        expect(Gate::allows($ability, $competition))->toBeFalse();
        expect(Gate::allows($ability, $result))->toBeFalse();
    }
});

test('an admin can manage teams, competitions, and results of any event', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();
    $event = Event::factory()->for($other)->create();
    $team = Team::factory()->for($event)->create();
    $competition = Competition::factory()->for($event)->create();
    $result = Result::factory()->for($team)->for($competition)->create();

    $this->actingAs($admin);

    foreach (['view', 'update', 'delete'] as $ability) {
        expect(Gate::allows($ability, $team))->toBeTrue();
        expect(Gate::allows($ability, $competition))->toBeTrue();
        expect(Gate::allows($ability, $result))->toBeTrue();
    }
});