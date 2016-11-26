<?php

// For testing convenience purposes, to be able to run this website with PHP's
// built-in web server. This imitates the `mod_rewrite` Apache directive.
// php -S localhost:7676 -t web web/route.php

// Monkey-fix for the vulnerability below.
if ( ! in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1','::1',))) {
    exit("What are you doing ? ... No !");
}

// Vulnerability here, abuser may check for files' presence on server.
if (is_file(__DIR__ . '/' . $_SERVER["REQUEST_URI"])) {
    return false;
} else {
    include __DIR__ . '/app_dev.php';
}