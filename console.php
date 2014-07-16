#!/usr/bin/env php
<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 04:52
 */

require __DIR__."/vendor/autoload.php";
require __DIR__."/config/params.php";

use Rn2014\Command as Commands;
use Knp\Provider\ConsoleServiceProvider;
use Silex\Application;

$app = new Application;
$app = require __DIR__."/src/app.php";

$app->register(new ConsoleServiceProvider(), array(
    'console.name' => 'Rn2014 RabbitLdap Console',
    'console.version' => '1.0.0',
    'console.project_directory' => __DIR__.''
));

$app['console']->add(new Commands\RabbitSetupCommand);
$app['console']->add(new Commands\RabbitMonitorSetupCommand);
$app['console']->add(new Commands\RabbitTestSendCommand);
$app['console']->add(new Commands\RabbitLdapSetupCommand);
$app['console']->add(new Commands\RabbitLdapReceiverCommand);

$app['console']->add(new Commands\LdapLoginCommand());
$app['console']->add(new Commands\LdapTestLoginCommand());
$app['console']->add(new Commands\LdapChangePasswordCommand());
$app['console']->add(new Commands\LdapUserAddCommand());
$app['console']->add(new Commands\LdapUserRemoveCommand());
//$application->add(new Commands\LdapUserDisableCommand());
$app['console']->add(new Commands\LdapUserGroupCommand());

$app['console']->run();