<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/utils/connection.php';
require __DIR__ . '/../src/utils/fetch_data.php';
require __DIR__ . '/../src/repositories/client_repositorie.php';
require __DIR__ . '/../src/repositories/compte_repositorie.php';
require __DIR__ . '/../src/repositories/operation_repositorie.php';
require __DIR__ . '/../src/repositories/virement_repositorie.php';

$app = AppFactory::create();

$url_de_base = '';

$url_de_base .= substr($_SERVER['REQUEST_URI'], 0, strlen($_SERVER['REQUEST_URI']) - strlen(strrchr($_SERVER['REQUEST_URI'], '/')));

$app->setBasePath($url_de_base); //Chemin de base de l'API

$app->addErrorMiddleware(true, true, true);

$app->addBodyParsingMiddleware();

/**
 * AUTHENTIFICATION
 */

/**
 * Le middleware d'authentification JWT
 */
$app->add(new \Tuupola\Middleware\JwtAuthentication([
    "secure" => false,
    "path" => ["$url_de_base/"],
    "ignore" => ["$url_de_base/token"],
    "secret" => $_SERVER['SECRET_KEY'],
    "algorithm" => ["HS256"],
    "error" => function ($response, $arguments) {
        $data["status"] = "error";
        $data["message"] = $arguments["message"];

        $response->getBody()->write(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $response->withHeader("Content-Type", "application/json")->withStatus(401);
    },

]));
/**
 * Web service d'authentification, il n'est pas sécurisé.
 * Néanmoins il faut lui passer un client_id et un client_secret valide pour obtenir un
 * token.
 */
$app->post('/token', function ($request, $response, $args) {

    $requested_scopes = $request->getParsedBody();

    $client_id = $requested_scopes['client_id']??null;
    $client_secret = $requested_scopes["client_secret"]??"";
    if ($client_id == $_SERVER["CLIENT_ID"] and $client_secret == $_SERVER["CLIENT_SECRET"]) { // A vérirfier dans la base de données
        $now = new DateTime();
        $expire_token = $_SERVER["EXPIRE_TOKEN"];
        $future = new DateTime("+$expire_token minutes");
        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "data" => $requested_scopes
        ];
        $secret = $_SERVER["SECRET_KEY"];
        $token = \Firebase\JWT\JWT::encode($payload, $secret, "HS256");
        $data["status"] = "success";
        $data["access_token"] = $token;
        $data["expires"]= $future->getTimestamp() - $now->getTimestamp();

        $response->getBody()->write(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $response->withHeader("Content-Type", "application/json");
    }else{
        $data["status"] = "error";
        $data["message"] = "client_id ou client_secret incorrect";

        $response->getBody()->write(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        return $response->withHeader("Content-Type", "application/json")->withStatus(\Grpc\STATUS_NOT_FOUND);
    }
});


/**
 * WEB SERVICES RELATIFS AUX CLIENTS
 */
$app->get('/clients', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_clients());
    return fetch_data($request, $response, $args, $data);
});


$app->get('/client-id={id}', function (Request $request, Response $response, $args) {
    if (client_exists($args["id"])) {
        $data = array("status" => "success",
            "data" => (array)find_client($args["id"]));
    } else {
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

$app->post('/client-update', function (Request $request, Response $response, $args) {
    $is_updated = update_client( $request->getParsedBody());
    if (is_bool($is_updated) and $is_updated) {
        $data = array("status" => "success", "msg" => "Client Updated Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_updated");
    }
    return fetch_data($request, $response, $args, $data);
});


$app->post('/login', function (Request $request, Response $response, $args) {
    $is_logged = login($request->getParsedBody());
    if (is_bool($is_logged) && $is_logged) {
        $data = array("status" => "success", "data" => true);
    } else {
        $data = array("status" => "error", "msg" => "$is_logged");
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
        "data" => (array)find_comptes(1, $args["numero"]));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/comptes-epargne', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_comptes(2));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/compte-epargne-numero={numero}', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => (array)find_comptes(2, $args["numero"]));
    return fetch_data($request, $response, $args, $data);
});

$app->post('/compte-courant-create', function (Request $request, Response $response, $args) {
    $is_created = create_compte(1, $request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Account Created Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});

$app->post('/compte-set-status', function (Request $request, Response $response, $args) {
    $is_updated = update_compte($request->getParsedBody());
    if (is_bool($is_updated) && $is_updated) {
        $data = array("status" => "success", "msg" => "Status updated Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_updated");
    }
    return fetch_data($request, $response, $args, $data);
});

$app->post('/compte-epargne-create', function (Request $request, Response $response, $args) {
    $is_created = create_compte(2, $request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Account Created Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});


/**
 * WEB SERVICES RELATIFS AUX OPERATIONS
 */

$app->get('/retraits', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => find_operations(1));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/versements', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => find_operations(2));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/retrait-code={code}', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => find_operation_by_code(1, $args["code"]));
    return fetch_data($request, $response, $args, $data);
});

$app->get('/versement-code={code}', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => find_operation_by_code(2, $args["code"]));
    return fetch_data($request, $response, $args, $data);
});


$app->post('/retrait-create', function (Request $request, Response $response, $args) {
    $is_created = create_operation(1/** retrait*/, $request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Withdraw Done Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});

$app->post('/versement-create', function (Request $request, Response $response, $args) {
    $is_created = create_operation(2/** depôt **/, $request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Deposit done Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});


$app->post('/virement-create', function (Request $request, Response $response, $args) {
    $is_created = create_virement($request->getParsedBody());
    if (is_bool($is_created) && $is_created) {
        $data = array("status" => "success", "msg" => "Account Created Successfully");
    } else {
        $data = array("status" => "error", "msg" => "$is_created");
    }
    return fetch_data($request, $response, $args, $data);
});


$app->get('/virements', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => find_virements());
    return fetch_data($request, $response, $args, $data);
});

$app->get('/virement-code={code}', function (Request $request, Response $response, $args) {
    $data = array("status" => "success",
        "data" => find_virement_by_code($args["code"]));
    return fetch_data($request, $response, $args, $data);
});
$app->run();
