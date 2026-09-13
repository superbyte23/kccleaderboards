<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('user management is admin-only', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($member);

    expect(Gate::allows('viewAny', User::class))->toBeFalse();
    expect(Gate::allows('create', User::class))->toBeFalse();
    expect(Gate::allows('update', $admin))->toBeFalse();

    $this->actingAs($admin);

    expect(Gate::allows('viewAny', User::class))->toBeTrue();
    expect(Gate::allows('create', User::class))->toBeTrue();
    expect(Gate::allows('update', $member))->toBeTrue();
});

test('an admin cannot delete themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    expect(Gate::allows('delete', $admin))->toBeFalse();
});

test('an admin can delete another user', function () {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    $this->actingAs($admin);

    expect(Gate::allows('delete', $member))->toBeTrue();
});