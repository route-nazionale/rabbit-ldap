<?php
/**
 * User: lancio
 * Date: 10/07/14
 * Time: 02:23
 */

require __DIR__ . "/../config/params.php";
require __DIR__ . "/../vendor/autoload.php";

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider as Providers;
use Rn2014\AESEncoder;

$app = new Application();

$app['debug'] = true;

$app->register(new Providers\TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../views',
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

$app['aes.encoder'] = $app->share(function() use ($app, $key, $iv) {

    if (AES_IV && AES_KEY) {

        $iv = AES_IV;
        $key = AES_KEY;

    } else {

        $sql = "SELECT * FROM crypt LIMIT 1";
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

    $ldapCaller = new \Rn2014\Ldap\LdapRawCaller($params);
    $ldap = new \Rn2014\Ldap\LdapCommander($ldapCaller);

    return $ldap;
});

$checkJsonRequest = (function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }else
        return new JsonResponse(["content" => "JsonData Missing"], 406);
});

/**
 * ROUTES
 */
$app->get("/test", function() use ($app){

    $params = [];
    return $app['twig']->render("index.html.twig", $params);

})->bind('test.form');

$app->post("/test", function() use ($app){

    $username  = $app['request']->get('username', null);
    $password = $app['request']->get('password', null);

    try {
        $response = $app['ldap']->testLogin($username, $password);

    } catch (\Exception $e) {

        return $app->abort($e->getMessage());
    }

    if ($response) {
        $app['session']->getFlashBag()->add('success', 'Account corretto');
    } else {
        $app['session']->getFlashBag()->add('error', 'Account sbagliato');
    }

    return $app->redirect($app['url_generator']->generate('test.form'), 301);

})->bind('test.form.validate');

$app->post("/login", function() use ($app){

    $group  = $app['request']->get('group', null);
    $username  = $app['request']->get('username', null);
    $encodedPassword = $app['request']->get('password', null);

    $decodedPassword = $app['aes.encoder']->decode($encodedPassword);

    try {

        $response = $app['ldap']->attemptLogin($username, $decodedPassword, $group);

    } catch (\Exception $e) {

        return new Symfony\Component\HttpFoundation\JsonResponse(["error" => $e->getMessage()], 500);
    }

    if ($response) {
        $response = ['logged' => true];
    } else {
        $response = ['logged' => false];
    }

    return new Symfony\Component\HttpFoundation\JsonResponse($response);

})->before($checkJsonRequest);

$app->get("/encode/{password}", function($password) use ($app){

    return $app['aes.encoder']->encode($password);
});

$app->get("/decode", function() use ($app){

    $password = $app['request']->query->get('password', '');
    echo $app['aes.encoder']->decode($password);;
    return $app['aes.encoder']->decode($password);
});

$app->run();


