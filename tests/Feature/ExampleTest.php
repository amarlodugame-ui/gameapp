<?php

test('returns a successful response', function () {
    $response = $this->get(route('user.home'));

    $response->assertOk();
});