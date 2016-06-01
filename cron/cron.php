<?php

#
# The purpose of this file is to be called in the CLI by CRON, like so:
# php cron/cron.php daily
#
# It should always be safe to call multiple times.
#
# NOTE
# there's probably a simpler way we can do this only with web/app.php
# this file should be removed if we can.
#

// For every line in this config there must exist respective routes and
// controller actions.
$freqs = array(
    'monkey',
    'daily',
);

$freq = $argv[1];
if ( ! in_array($freq, $freqs)) {
    exit("Wrong frequency ! Allowed frequencies : ".join(', ', $freqs)."\n");
}

require_once __DIR__.'/../app/bootstrap.php.cache';
require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('prod', false);
$kernel->loadClassCache();


// Let's make a dummy request, to trigger the CronController::dailyAction()
$request = new Symfony\Component\HttpFoundation\Request();
$request = $request->create("/v1/cron/".$freq);

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
