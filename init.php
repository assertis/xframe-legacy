<?php

//Object Factory
require_once(dirname(__FILE__)."/model/core/Factory.php");
require_once(dirname(__FILE__)."/model/core/Autoloader.php");

$autloader = new model\core\Autoloader();
$autloader->register();

Factory::init();
Controller::boot();
Registry::init();
Cache::init();
Registry::loadDBSettings();
FrameEx::init();

Factory::attachAutoloader($autloader);

//boot the app
Factory::boot(APP_DIR);