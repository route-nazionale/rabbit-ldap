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

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;


$app = new Application;
$app = require __DIR__."/src/app.php";

$app->register(new ConsoleServiceProvider(), array(
    'console.name' => 'Rn2014 RabbitLdap Console',
    'console.version' => '1.0.0',
    'console.project_directory' => __DIR__.''
));

$app['monolog.import.logfile'] = __DIR__ . '/logs/import.log';
$app['monolog.import.level'] = \Monolog\Logger::DEBUG;
$app['monolog.import'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('import');
    $handler = new \Monolog\Handler\StreamHandler($app['monolog.import.logfile'], $app['monolog.import.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['rabbit']= $app->share(function() use ($app) {

    $host = RABBITMQ_HOST;
    $port = RABBITMQ_PORT;
    $user = RABBITMQ_USER;
    $password = RABBITMQ_PASS;
    $vhost = RABBITMQ_VHOST;

    if (RABBITMQ_SSL) {

        $ssl_options = array(
            'capath' => RABBITMQ_SSL_CAPATH,
            'cafile' => RABBITMQ_SSL_CAFILE,
            'verify_peer' => RABBITMQ_SSL_VERIFY_PEER,
        );

        $connection = new AMQPSSLConnection($host, $port, $user, $password, $vhost, $ssl_options);

    } else {
        $connection = new AMQPConnection($host, $port, $user, $password, $vhost);
    }

    return $connection;
});

$app['console']->add(new Commands\ImportUserCommand($app['ldap.admin'], $app['dbs']['aquile_randagie'], $app['monolog.import']));
$app['console']->add(new Commands\ImportUserChangeGroupCommand($app['ldap.admin'], $app['dbs']['aquile_randagie'], $app['monolog.import']));

$app['console']->add(new Commands\RabbitSetupCommand($app['rabbit']));
$app['console']->add(new Commands\RabbitMonitorSetupCommand($app['rabbit']));
$app['console']->add(new Commands\RabbitTestSendCommand($app['rabbit'], $app['aes.encoder']));
$app['console']->add(new Commands\RabbitLdapSetupCommand($app['rabbit']));
$app['console']->add(new Commands\RabbitReceiverCommand($app, $app['rabbit'], $app['aes.encoder']));

$app['console']->add(new Commands\LdapLoginCommand($app['ldap']));
$app['console']->add(new Commands\LdapTestLoginCommand($app['ldap']));
$app['console']->add(new Commands\LdapChangePasswordCommand($app['ldap.admin']));
$app['console']->add(new Commands\LdapResetPasswordCommand($app['ldap']));
$app['console']->add(new Commands\LdapUserAddCommand($app['ldap.admin']));
//$app['console']->add(new Commands\LdapUserRemoveCommand($app['ldap.admin']));
//$application->add(new Commands\LdapUserDisableCommand($app['ldap.admin']));
$app['console']->add(new Commands\LdapUserGroupCommand($app['ldap.admin']));
$app['console']->add(new Commands\LdapUserGroupsCommand($app['ldap.admin']));
$app['console']->add(new Commands\LdapGroupsCommand($app['ldap.admin']));
$app['console']->add(new Commands\LdapUsersCommand($app['ldap.admin']));
$app['console']->add(new Commands\LdapSyncGroupsOnDbCommand($app['ldap.admin'],$app['dbs']['posix'], $app['monolog.syncdb']));

$app['console']->run();
