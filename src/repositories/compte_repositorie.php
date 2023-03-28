<?php
/**
 * @param $type
 * @param $data
 * @return string|true
 */
function create_compte($type, $data)
{
    $numero = uniqid("cp", true); //numero unique
    $solde = $data["solde"];
    if ($type == 1) { // compte courant
        $decouvert = $data["decouvert"] ?? null;
    } elseif ($type == 2) { // compte épargne
        $taux = $data["taux"] ?? null;
    }

    $id_client = $data["client"];
    $statut = 1;
    if (!client_exists($id_client)) return "Client Not Found";
    if (!type_compte_exists($type)) return "Type Not Found";
    try {
        $db = $GLOBALS['db'];
        $db->beginTransaction();
        $QUERY = "INSERT INTO comptes (numero,id_type_compte,solde,taux,decouvert,id_client,statut) VALUES (:numero,:id_type_compte,:solde,:taux,:decouvert,:id_client,:statut)";
        $preparedStatement = $db->prepare($QUERY);
        $preparedStatement->bindParam(':numero', $numero);
        $preparedStatement->bindParam(':id_type_compte', $type);
        $preparedStatement->bindParam(':solde', $solde);
        $preparedStatement->bindParam(':taux', $taux);
        $preparedStatement->bindParam(':decouvert', $decouvert);
        $preparedStatement->bindParam(':id_client', $id_client);
        $preparedStatement->bindParam(':statut', $statut);
        $preparedStatement->execute();
        $compte = get_last_created_compte();
        $QUERY = "INSERT INTO client_has_comptes (id_compte,id_client) VALUES ($compte->id,$id_client)";
        $preparedStatement = $db->prepare($QUERY);
        $preparedStatement->execute();
        $db->commit();
        return true;

    } catch (Exception $ex) {
        $db->rollBack();
        return $ex->getMessage();
    }
}

;

/**
 * @return mixed
 */
function find_comptes($type, $numero = "", $client = 0)
{
    $db = $GLOBALS['db'];
    if ($type == 1)
        $QUERY = "SELECT DISTINCT c.id, c.numero,t.libelle as type,c.solde,c.decouvert,c.id_client,t.id as id_type,s.libelle_statut as statut FROM comptes c,type_compte t,statut_compte s WHERE c.id_type_compte=t.id AND c.statut=s.id AND t.id = $type";
    elseif ($type == 2)
        $QUERY = "SELECT DISTINCT c.id, c.numero,t.libelle as type,c.solde,c.taux ,c.id_client ,t.id as id_type,s.libelle_statut as statut FROM comptes c,type_compte t,statut_compte s WHERE c.id_type_compte=t.id AND c.statut=s.id AND t.id = $type";
    elseif ($type == 0)
        $QUERY = "SELECT DISTINCT c.id, c.numero,t.libelle as type,c.solde,c.taux ,c.id_client, t.id as id_type,s.libelle_statut as statut FROM comptes c,type_compte t,statut_compte s WHERE c.id_type_compte=t.id AND c.statut=s.id";
    if (!empty($numero)) {
        $QUERY .= " AND c.numero = '$numero'";
    }

    if ($client != 0) {
        $QUERY .= " AND c.id_client=$client";
    }
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    $comptes = (array)$records;
    $clients = (array)find_clients();
    $data = array();
    $temp_data = array();
    $opers = array(); //variable temporaire
    foreach ($comptes as $compte) {
        foreach ($clients as $client) {
            $compte = (array)$compte;
            $client = (array)$client;
            $operations = (array)simple_select_operation_by_id_compte($compte['id']);
            $ops = &$operations;
            if ($compte['id_client'] == $client['id']) {
                foreach ($ops as $operation) {
                    $operation = (array)$operation;
                    unset($operation["id_compte"]);
                    $operation["type"] = ($operation["id_type_operation"] == 1) ? 'retrait' : 'versement';
                    unset($operation["id_type_operation"]);
                    unset($operation['id']);
                    array_push($opers, $operation);


                }
                unset($client["id"]);
                unset($compte["id"]);
                unset($compte["id_type"]);
                unset($client["comptes"]);
                $record = $compte;
                unset($record['id_client']);
                $record["client"] = (array)$client;
                $record["operations"] = $ops;

                array_push($data, $record);
            }
        }

    }

    return $data;
}

function find_compte($numero)
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM comptes WHERE numero = '$numero'";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    if (sizeof((array)$records) > 0) {
        return $records[0];
    } else {
        return "Account Not Found";
    }
}

function find_compte_by_id($id)
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM comptes WHERE id = '$id'";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    if (sizeof((array)$records) > 0) {
        return $records[0];
    } else {
        return "Account Not Found";
    }
}

function type_compte_exists($type)
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM type_compte WHERE id = '$type'";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    if (sizeof((array)$records) > 0) {
        return true;
    } else {
        return false;
    }
}

function update_compte($data)
{
    $numero = isset($data["numero"])?$data["numero"]:"";
    if(empty($numero)) return "Numero is required";
    $compte = (array)find_compte($numero);
    $solde = ($data["solde"]) ?? $compte["solde"];
    $taux = ($data["taux"]) ?? $compte["taux"];
    $statut = (int)($data["statut"])??$compte["statut"];
    $statut_array = array(1,2,3);
    if(!in_array($statut,$statut_array)) return "Statut must be 1 : actif,2: bloqué or 3:suspendu";
    $decouvert = ($data["decouvert"]) ?? $compte["decouvert"];
    try {
        $db = $GLOBALS['db'];
        $QUERY = "UPDATE comptes SET solde = :solde,taux = :taux,decouvert= :decouvert,statut= :statut WHERE numero= :numero";
        $preparedStatement = $db->prepare($QUERY);
        $preparedStatement->bindParam(':solde', $solde);
        $preparedStatement->bindParam(':taux', $taux);
        $preparedStatement->bindParam(':decouvert', $decouvert);
        $preparedStatement->bindParam(':numero', $data["numero"]);
        $preparedStatement->bindParam(':statut', $statut);
        $preparedStatement->execute();
        return true;
    } catch (Exception $ex) {
        return $ex->getMessage();
    }
}

function get_last_created_compte()
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM comptes ORDER  BY id DESC  LIMIT 1";
    $record = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    return $record[0];
}
