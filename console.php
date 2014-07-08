#!/usr/bin/env php
<?php
/**
 * User: lancio
 * Date: 05/07/14
 * Time: 04:52
 */

use Rn2014\Command as Commands;
use Symfony\Component\Console\Application;

require __DIR__."/vendor/autoload.php";
require __DIR__."/config/params.php";

$application = new Application();
$application->add(new Commands\RabbitSetupCommand);
$application->add(new Commands\RabbitMonitorSetupCommand);
$application->add(new Commands\RabbitTestSendCommand);
$application->add(new Commands\RabbitLdapSetupCommand);
$application->add(new Commands\RabbitLdapReceiverCommand);

$application->add(new Commands\LdapLoginCommand());
$application->add(new Commands\LdapTestLoginCommand());
$application->add(new Commands\LdapChangePasswordCommand());
$application->add(new Commands\LdapAddUserCommand());

$application->run();