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

$app['aes.encoder'] = $app->share(function() use ($app) {
    $key = file_get_contents("cert.pem");
    $privateKey = file_get_contents("key.pem");

    return new AESEncoder($key,$privateKey);
});

$app['ldap'] = $app->share(function() use ($app) {

    $params = [
        'hostname'      => LDAP_HOST,
        'port'          => LDAP_PORT,
        'security'      => LDAP_SECURITY,
        'base_dn'       => LDAP_BASE_DN,
        'options'       => [LDAP_OPT_PROTOCOL_VERSION => LDAP_VERSION],
//            'admin'         => [
//                'dn'        => LDAP_ADMIN_DN,
//                'password'  => LDAP_ADMIN_PASSWORD,
//            ]
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

})->before("checkJsonRequest");

$app->get("/encode/{password}", function($password) use ($app){

    return $app['aes.encoder']->encode($password);
});

$app->get("/decode/{password}", function($password) use ($app){

    return $app['aes.encoder']->decode($password);
});

$app->run();