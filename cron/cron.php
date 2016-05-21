<?php

# The purpose of this file is to be called in the CLI by CRON.

$freq = $argv[1];
$freqs = array('daily');
if ( ! in_array($freq, $freqs)) {
    exit("Wrong frequency ! Allowed frequencies : ".join(', ', $freqs)."\n");
}

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();

// fixme: ensure this cannot be called more than once per minute (or hour) DDOS!
// Because I want to be able to call this CRON task whenever I want,
// but it's an expensive task, so it puts a heavy load on the server.
// This is a very good entry point for DDOSes.

// todo: there's probably a simpler way we can do this only with web/app.php
//       this file should be removed if we can.

// Let's make a dummy request, to trigger the CronController::dailyAction()
$request = new Symfony\Component\HttpFoundation\Request();
$request = $request->create("/v1/cron/".$freq);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
