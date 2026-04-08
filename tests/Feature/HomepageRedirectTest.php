<?php

it('redirects homepage to login page', function (): void {
    $this->get('/')->assertRedirect('/login');
});
