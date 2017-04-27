<?php

if (is_readable(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
}

Controller::boot();
Registry::init();
Cache::init();
Registry::loadDBSettings();
ErrorHandler::init();

//boot the app
Factory::boot(APP_DIR);