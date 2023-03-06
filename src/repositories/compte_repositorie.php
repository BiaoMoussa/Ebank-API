<?php
/**
 * @param $type
 * @param $data
 * @return string|true
 */
function create_compte($type,$data)
{
    $numero = uniqid("cp", true); //numero unique
    $solde = $data["solde"];
    if($type == 1){ // compte courant
        $decouvert = $data["decouvert"] ?? null;
    }elseif ($type == 2){ // compte épargne
        $taux = $data["taux"] ?? null;
    }

    $id_client = $data["client"];
    if (!client_exists($id_client)) return "Client Not Found";
    if (!type_compte_exists($type) ) return "Type Not Found";
    try {
        $db = $GLOBALS['db'];
        $db->beginTransaction();
        $QUERY = "INSERT INTO comptes (numero,id_type_compte,solde,taux,decouvert,id_client) VALUES (:numero,:id_type_compte,:solde,:taux,:decouvert,:id_client)";
        $preparedStatement = $db->prepare($QUERY);
        $preparedStatement->bindParam(':numero', $numero);
        $preparedStatement->bindParam(':id_type_compte', $type);
        $preparedStatement->bindParam(':solde', $solde);
        $preparedStatement->bindParam(':taux', $taux);
        $preparedStatement->bindParam(':decouvert', $decouvert);
        $preparedStatement->bindParam(':id_client', $id_client);
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
function find_comptes($type,$numero="",$client=0)
{
    $db = $GLOBALS['db'];
    if ($type == 1)
        $QUERY = "SELECT DISTINCT c.numero,t.libelle as type,c.solde,c.decouvert,c.id_client FROM comptes c,type_compte t WHERE c.id_type_compte=t.id AND t.id = $type";
    elseif ($type == 2)
        $QUERY = "SELECT DISTINCT c.numero,t.libelle as type,c.solde,c.taux ,c.id_client FROM comptes c,type_compte t WHERE c.id_type_compte=t.id AND t.id = $type";
    elseif ($type == 0)
        $QUERY = "SELECT DISTINCT c.numero,t.libelle as type,c.solde,c.taux ,c.id_client FROM comptes c,type_compte t WHERE c.id_type_compte=t.id";
    if(!empty($numero)){
        $QUERY .= " AND c.numero = '$numero'";
    }

    if($client!=0){
        $QUERY .= " AND c.id_client=$client";
    }
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    $comptes = (array)$records;
    $clients = (array)find_clients();
    $data = array();
    foreach ($comptes as $compte) {
        foreach ($clients as $client) {
            $compte = (array)$compte;
            $client = (array)$client;
            if ($compte['id_client'] == $client['id']) {
                unset($client["comptes"]);
                $record = $compte;
                unset($record['id_client']);
                $record["client"] = (array)$client;
                array_push($data, $record);
            }
        }
    }
    return $data;
}

function find_compte($numero)
{
    $db = $GLOBALS['db'];
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

function get_last_created_compte()
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM comptes ORDER  BY id DESC  LIMIT 1";
    $record = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    return $record[0];
}