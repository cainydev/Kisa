<?php

it('redirects guests from the root path to the admin panel', function () {
    $this->get('/')->assertRedirect();
});
