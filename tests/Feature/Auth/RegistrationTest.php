<?php

test('registration is disabled', function () {
    $response = $this->get('/register');

    $response->assertNotFound();
});

test('login route still works', function () {
    $this->get(route('login'))->assertOk();
});