<?php
/**
 * User: lancio
 * Date: 15/07/14
 * Time: 01:38
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

$checkJsonRequest = (function (Request $request) {
    if (0 === strpos(strtolower($request->headers->get('Content-Type')), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }else
        return new JsonResponse(["content" => "JsonData Missing"], 406);
});

$app->before(function() use ($app){

    if (HTTPS_REQUIRED) {
        $app->get('_controller')->requireHttps();
    }
});

/**
 * ROUTES
 */
$app->get("/", function() use ($app){

    return $app['twig']->render("index.html.twig", []);
})
    ->bind('test.form');

$app->post("/", function() use ($app){

    $username  = $app['request']->get('username', null);
    $password = $app['request']->get('password', null);

    try {
        $response = $app['auth']->testLogin($username, $password);

    } catch (\Exception $e) {

        return $app->abort($e->getMessage());
    }

    if ($response) {
        $app['session']->getFlashBag()->add('success', 'Account corretto');
    } else {
        $app['session']->getFlashBag()->add('error', 'Account sbagliato');
    }

    $context = [
        'username' => $username,
        'password' => str_repeat('*', strlen($password)),
        'result' => $response,
        'ip' => $app['request']->getClientIps(),
        'user_agent' => $app['request']->headers->get('User-Agent'),
    ];
    $app['monolog.login']->addInfo("test login", $context);

    return $app->redirect($app['url_generator']->generate('test.form'), 301);

})
    ->bind('test.form.validate');

$app->post("/login", function() use ($app){

    $response = false;

    $encodedBirthdate  = $app['request']->get('birthdate', null);
    $group  = $app['request']->get('group', null);
    $username  = $app['request']->get('username', null);
    $encodedPassword = $app['request']->get('password', null);

    if (!$group || !$username || !($encodedPassword || $encodedBirthdate)) {
        return new JsonResponse(null,401);
    }

    $decodedBirthdate = false;
    $decodedPassword = false;

    try {

        if ($encodedPassword) {
            $decodedPassword = $app['aes.encoder']->decode($encodedPassword);

            $response = $app['auth']->attemptLogin($username, $decodedPassword, $group);

        } else {

            if( $encodedBirthdate) {

                $decodedBirthdate = $app['aes.encoder']->decode($encodedBirthdate);

                $response = $app['auth']->attemptLoginWithBirthdate($username, $decodedBirthdate, $group);
            }
        }
    } catch (\Exception $e) {
        $context = [
            'username' => $username,
            'password' => $encodedPassword,
            'birthdate' => $encodedBirthdate,
            'result' => $response,
            'ip' => $app['request']->getClientIps(),
            'user_agent' => $app['request']->headers->get('User-Agent'),
            'exception' => $e->getMessage()
        ];
        $app['monolog.login']->addError("login", $context);

        return new JsonResponse(["error" => $e->getMessage()], 500);
    }

    if ($response) {
        $responseCode = 204;
    } else {
        $responseCode = 403;
    }

    $context = [
        'username' => $username,
        'birthdate' => str_repeat('*', strlen($decodedBirthdate)),
        'password' => str_repeat('*', strlen($decodedPassword)),
        'group' => $group,
        'result' => $response,
        'ip' => $app['request']->getClientIps(),
        'user_agent' => $app['request']->headers->get('User-Agent'),
    ];
    $app['monolog.login']->addInfo("login", $context);

    return new JsonResponse(null, $responseCode);
})
    ->before($checkJsonRequest);

$app->get("/users/{username}/groups", function($username) use ($app){

    $groups = $app['ldap.admin']->getUserGroups($username);

    return $app->json(["groups" => $groups]);
});
//    ->before($checkJsonRequest);


if ($app['debug']) {

    $app->get("/encode/{password}", function($password) use ($app){

        return $app['aes.encoder']->encode($password);
    });

    $app->get("/decode", function() use ($app){

        $password = $app['request']->query->get('password', '');

        return $app['aes.encoder']->decode($password);
    });
}

$app->error(function (\Exception $e, $code) use ($app) {

    // commented for testing purposes
    if ($app['debug']) {
        return;
    }

    if ($code == 404) {

        $data = array(
            'title' => "Ti sei perso? usa la bussola!"
        );

        return new Response( $app['twig']->render('404.html.twig', $data), 404);

    } elseif ($code == 500) {

        $data = array(
            'title' => "C'è stato un problema."
        );
        return new Response( $app['twig']->render('500.html.twig', $data), 500);
    }

    return new Response('Spiacenti, c\'è stato un problema.', $code);
});

return $app;