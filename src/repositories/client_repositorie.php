<?php
/**
 * Fonction permettant de créer un nouveau client
 * @param string $nom
 * @param string $prenom
 * @param string $email
 */
function create_client($data)
{
    $nom = $data["nom"];
    $prenom = $data["prenom"];
    $email = $data["email"];
    $email_exists = email_already_exists($email);
    if ($email_exists) {
        return false;
    }
    try {
        $db = $GLOBALS['db'];
        $QUERY = "INSERT INTO client (nom,prenom,email) VALUES (:nom,:prenom,:email)";
        $preparedStatement = $db->prepare($QUERY);
        $preparedStatement->bindParam(':nom', $nom);
        $preparedStatement->bindParam(':prenom', $prenom);
        $preparedStatement->bindParam(':email', $email);
        $preparedStatement->execute();
        return true;
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

;

/**
 * @return mixed
 */
function find_clients()
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM client";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    $clients = array();
    foreach ($records as $client){
        $comptes = comptes_of_client($client->id);
        $client = (array)$client;
        $client['comptes'] = $comptes;
        array_push($clients,$client);
    }
    return $clients;
}

/**
 * @param $id
 * @return mixed
 */
function find_client($id)
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM client WHERE id=$id";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    $client = (array)$records[0];
    $client['comptes'] = comptes_of_client($id);
    return $client;
}

/**
 * @param $email
 * @return bool
 */
function email_already_exists($email)
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM client WHERE email = '$email'";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    if (sizeof((array)$records) > 0) {
        return true;
    } else {
        return false;
    }

}

/**
 * @param $id
 * @param $data
 * @return string|true
 */
function update_client($id, $data)
{
    if (!client_exists($id)) return "Client not found";
    $client = (array)find_client($id)[0];
    $nom = $data["nom"] ?? $client["nom"];
    $prenom = $data["prenom"] ?? $client["prenom"];
    $email = $data["email"] ?? $client["email"];
    if ($client["email"] != $data["email"] && email_already_exists($email)) {
        return "Email already exists";
    }
    try {
        $db = $GLOBALS['db'];
        $QUERY = "UPDATE client SET nom = :nom,prenom = :prenom,email= :email WHERE id=$id";
        $preparedStatement = $db->prepare($QUERY);
        $preparedStatement->bindParam(':nom', $nom);
        $preparedStatement->bindParam(':prenom', $prenom);
        $preparedStatement->bindParam(':email', $email);
        $preparedStatement->execute();
        return true;
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

/**
 * @param $id
 * @return bool
 */
function client_exists($id)
{
    $records = find_client($id);
    if (sizeof((array)$records) > 0) {
        return true;
    } else {
        return false;
    }

}


function comptes_of_client($id)
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT DISTINCT c.* FROM comptes c, client_has_comptes chc WHERE c.id = chc.id_compte AND chc.id_client=$id";
    $comptes = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    $cpte_epargnes = array();
    $cpte_courants = array();
    foreach ($comptes as $compte) {
        $compte = (array)$compte;
        if ($compte["id_type_compte"] == 1) {
            unset($compte["taux"]);
            unset($compte["id_type_compte"]);
            unset($compte["id_client"]);
            $compte["type"] = 'courant';
            array_push($cpte_courants, $compte);
        } elseif ($compte["id_type_compte"] == 2) {
            unset($compte["decouvert"]);
            unset($compte["id_type_compte"]);
            unset($compte["id_client"]);
            $compte["type"] = 'épargne';
            array_push($cpte_epargnes, $compte);
        }

    }
    return array_merge($cpte_courants,$cpte_epargnes);
}