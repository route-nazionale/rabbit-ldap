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
use Rn2014\Auth\Auth;
use Rn2014\Auth\TemporaryAuth;

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
    'dbs.options' => [
        'aes' => [
            'driver'   => 'pdo_mysql',
            'host'     => MYSQL_HOST,
            'port'     => MYSQL_PORT,
            'dbname'     => MYSQL_DB_AES,
            'user'     => MYSQL_USER_AES,
            'password'     => MYSQL_PASS_AES,
            'charset'     => 'utf8',
        ],
        'ldap' => [
            'driver'   => 'pdo_mysql',
            'host'     => MYSQL_HOST,
            'port'     => MYSQL_PORT,
            'dbname'     => MYSQL_DB_LDAP,
            'user'     => MYSQL_USER_LDAP,
            'password'     => MYSQL_PASS_LDAP,
            'charset'     => 'utf8',
        ],
        'posix' => [
            'driver'   => 'pdo_mysql',
            'host'     => MYSQL_HOST,
            'port'     => MYSQL_PORT,
            'dbname'     => MYSQL_DB_LDAP_POSIX,
            'user'     => MYSQL_USER_LDAP_POSIX,
            'password'     => MYSQL_PASS_LDAP_POSIX,
            'charset'     => 'utf8',
        ],
        'aquile_randagie' => [
            'driver'   => 'pdo_mysql',
            'host'     => MYSQL_HOST,
            'port'     => MYSQL_PORT,
            'dbname'     => MYSQL_DB_AQUILE,
            'user'     => MYSQL_USER_AQUILE,
            'password'     => MYSQL_PASS_AQUILE,
            'charset'     => 'utf8',
        ],
    ],
]);

/**
 * Loggers
 */
$app['monolog.login.logfile'] = __DIR__ . '/../logs/auth.log';
$app['monolog.login.level'] = Logger::INFO;
$app['monolog.login'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('login');
    $handler = new StreamHandler($app['monolog.login.logfile'], $app['monolog.login.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['monolog.humen.logfile'] = __DIR__ . '/../logs/humen.log';
$app['monolog.humen.level'] = Logger::INFO;
$app['monolog.humen'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('humen');
    $handler = new StreamHandler($app['monolog.humen.logfile'], $app['monolog.humen.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['monolog.syncdb.logfile'] = __DIR__ . '/../logs/syncdb.log';
$app['monolog.syncdb.level'] = Logger::INFO;
$app['monolog.syncdb'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('syncdb');
    $handler = new StreamHandler($app['monolog.syncdb.logfile'], $app['monolog.syncdb.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['monolog.ldap.logfile'] = __DIR__ . '/../logs/ldap.log';
$app['monolog.ldap.level'] = Logger::DEBUG;

$app['monolog.ldap'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('ldap');
    $handler = new StreamHandler($app['monolog.ldap.logfile'], $app['monolog.ldap.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['monolog.ldap.admin'] = $app->share(function ($app) {
    $log = new $app['monolog.logger.class']('ldap.admin');
    $handler = new StreamHandler($app['monolog.ldap.logfile'], $app['monolog.ldap.level']);
    $log->pushHandler($handler);

    return $log;
});

$app['aes.encoder'] = $app->share(function() use ($app) {

    if (AES_IV && AES_KEY) {

        $iv = AES_IV;
        $key = AES_KEY;

    } else {

        $sql = "SELECT * FROM aes LIMIT 1";
        $cryptData = $app['dbs']['aes']->fetchAssoc($sql);

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
    $ldap = new LdapCommander($ldapCaller, $app['monolog.ldap']);

    return $ldap;
});

$app['ldap.admin'] = $app->share(function() use ($app) {

    $params = [
        'hostname'      => LDAP_HOST,
        'port'          => LDAP_PORT,
        'security'      => LDAP_SECURITY,
        'base_dn'       => LDAP_BASE_DN,
        'options'       => [LDAP_OPT_PROTOCOL_VERSION => LDAP_VERSION],
        'admin'         => [
            'dn'        => LDAP_ADMIN_DN,
            'password'  => LDAP_ADMIN_PASSWORD,
        ]
    ];

    $ldapCaller = new LdapRawCaller($params);
    $ldap = new LdapCommander($ldapCaller,$app['monolog.ldap.admin']);

    return $ldap;
});

$app['auth'] = $app->share(function() use ($app) {
    switch (LOGIN_METHOD) {
        case 'ldap':
            return new Auth($app['dbs']['aquile_randagie'], $app['ldap']);
        case 'temp':
            return new TemporaryAuth($app['dbs']['ldap']);
        default:
            throw new \Exception ("Login method not correct");
    }
});

return $app;