<?php
/**
 * User: lancio
 * Date: 10/07/14
 * Time: 02:23
 */

require __DIR__ . "/../config/params.php";
require __DIR__ . "/../vendor/autoload.php";

use Silex\Application;

$app = new Application();

$app['debug'] = APP_DEBUG;

$app = require __DIR__ . "/../src/app.php";

$app = require __DIR__ . "/../src/routes.php";

$app->run();


