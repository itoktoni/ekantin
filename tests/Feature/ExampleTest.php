<?php

it('redirects guests to the login page', function () {
    // Halaman "/" (CMS PublicController) berada di belakang auth — guest diarahkan ke /login.
    $this->get('/')->assertRedirect('/login');
});
