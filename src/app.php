<?php
/**
 * User: lancio
 * Date: 15/07/14
 * Time: 01:32
 */

use Silex\Provider as Providers;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Rn2014\AESEncoder;
use Rn2014\Ldap\LdapRawCaller;
use Rn2014\Ldap\LdapCommander;

$app->register(new Providers\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../views',
]);

$app->register(new Providers\MonologServiceProvider(),[
    'monolog.logfile' => __DIR__.'/../logs/development.log',
    'monolog.name' => 'auth',
    'monolog.level' => Logger::WARNING,
]);

$app->register(new Providers\SessionServiceProvider());

$app->register(new Providers\UrlGeneratorServiceProvider());

$app->register(new Providers\DoctrineServiceProvider(), [
    'db.options' => [
        'driver'   => 'pdo_mysql',
        'host'     => MYSQL_HOST,
        'port'     => MYSQL_PORT,
        'dbname'     => MYSQL_DB,
        'user'     => MYSQL_USER,
        'password'     => MYSQL_PASS,
        'charset'     => 'utf8',
    ],
]);

$app['monolog.login.logfile'] = __DIR__ . '/../logs/auth.log';
$app['monolog.login.level'] = Logger::INFO;
$app['monolog.login'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('login');
    $handler = new StreamHandler($app['monolog.login.logfile'], $app['monolog.login.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['aes.encoder'] = $app->share(function() use ($app) {

    if (AES_IV && AES_KEY) {

        $iv = AES_IV;
        $key = AES_KEY;

    } else {

        $sql = "SELECT * FROM aes LIMIT 1";
        $cryptData = $app['db']->fetchAssoc($sql);

        if (!$cryptData) {
            throw new \Exception("key and iv not found");
        }

        $iv = base64_decode($cryptData['iv']);
        $key = base64_decode($cryptData['key']);
    }

    return new AESEncoder($key,$iv);
});

$app['ldap'] = $app->share(function() use ($app) {

    $params = [
        'hostname'      => LDAP_HOST,
        'port'          => LDAP_PORT,
        'security'      => LDAP_SECURITY,
        'base_dn'       => LDAP_BASE_DN,
        'options'       => [LDAP_OPT_PROTOCOL_VERSION => LDAP_VERSION],
    ];

    $ldapCaller = new LdapRawCaller($params);
    $ldap = new LdapCommander($ldapCaller);

    return $ldap;
});

return $app;