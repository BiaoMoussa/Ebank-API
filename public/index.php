<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/utils/connection.php';
require __DIR__ . '/../src/utils/feth_data.php';
require __DIR__ . '/../src/repositories/client_repositorie.php';
require __DIR__ . '/../src/repositories/compte_repositorie.php';
$app = AppFactory::create();

$url_de_base = '';

$url_de_base .= substr($_SERVER['REQUEST_URI'], 0, strlen($_SERVER['REQUEST_URI']) - strlen(strrchr($_SERVER['REQUEST_URI'], '/')));

$app->setBasePath($url_de_base); //Chemin de base de l'API

$app->addErrorMiddleware(true, true, true);


$app->get('/clients', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_clients());
    return fetch_data($request, $response, $args, $data);
});

/**
 * WEB SERVICES RELATIFS AUX CLIENTS
 */

$app->get('/client-id={id}', function (Request $request, Response $response, $args) {
    if (client_exists($args["id"])) {
        $data = array("status" => "success",
            "data" => (array)find_client($args["id"]));
    }else{
        $data = array("status" => "success",
            "msg" => "Client Not Found");
    }

    return fetch_data($request, $response, $args, $data);
});

$app->post('/client-create', function (Request $request, Response $response, $args) {
    $is_created = create_client($request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Client Created Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});

$app->post('/client-update-{id}', function (Request $request, Response $response, $args) {
    $is_updated = update_client($args["id"], $request->getParsedBody());
    if (is_bool($is_updated) and $is_updated) {
        $data = array("status" => "success", "msg" => "Client Updated Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_updated");
    }
    return fetch_data($request, $response, $args, $data);
});


/**
 * FIN WEB SERVICES
 */

/**
 * WEB SERVICES RELATIFS AUX COMPTES
 */
$app->get('/comptes-courant', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_comptes(1));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/compte-courant-numero={numero}', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_comptes(1,$args["numero"]));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/comptes-epargne', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_comptes(2));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/compte-epargne-numero={numero}', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_comptes(2,$args["numero"]));
    return fetch_data($request, $response, $args, $data);
});

$app->post('/compte-courant-create', function (Request $request, Response $response, $args) {
    $is_created = create_compte(1,$request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Account Created Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});

$app->post('/compte-epargne-create', function (Request $request, Response $response, $args) {
    $is_created = create_compte(2,$request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Account Created Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});

$app->run();
