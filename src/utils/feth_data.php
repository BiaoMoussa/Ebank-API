<?php
/**
 * Pour afficher les données au format json au client
 * @param $request
 * @param $response
 * @param $args
 * @param $data
 * @return mixed
 */
function fetch_data($request,$response,$args,$data){
    try {

        //---------------------------------------------------------------------------------------------------------------
        $response->getBody()->write(json_encode($data)); //On écrit les données sur l'objet destiné à la reponse http
        //---------------------------------------------------------------------------------------------------------------


        //---------------------------------------------------------------------------------------------------------------------------------
        return $response->withHeader('content-type', 'application/json')->withStatus(200); // réponse au format json avec le code statut 200
        //---------------------------------------------------------------------------------------------------------------------------------

    } catch (Exception $erreur) {

        //---------------------------------------------------------------------------------------
        return $response->getBody()->write($erreur->getMessage()) //On renvioie le message d'erreur
        ->withHeader('content-type', 'application/json')      // Au format json
        ->withStatus(500);                                    // Avec le code statut 500

    }
    return $response;
}
