<?php

test('returns a successful response for home', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('returns a successful response for manga details', function () {
    $response = $this->get(route('mangas.show', ['id' => 13]));

    $response->assertOk();
});
