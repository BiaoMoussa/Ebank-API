<?php

/**
 * @param $type
 * @param $data
 * @return void
 */
function create_operation($type, $data)
{
    $montant_min = 1000;
    $db = $GLOBALS["db"];
    if (!isset($data["montant"])) {
        return "Amount is required !";
    } else {
        if ($data["montant"] < $montant_min) {
            return "Amount must be great than $montant_min";
        }
        $montant = $data["montant"];
    }

    if (!isset($data["compte"])) {
        return "Account is required !";
    } else {
        if (empty($data["compte"])) return "Account can't be null";
        $compte = find_compte($data["compte"]);
        $compteModifier = (array)$compte;
        $id_compte = $compte->id;
        $type_compte = $compte->id_type_compte;
        $solde = $compte->solde;
        $statut = $compte->statut;
        if($statut!=1) return "Account Blocked or Suspended";
    }
    if ($type != 1 && $type != 2) {
        return "Type of Operation Not Found";
    }

    switch ((int)$type) {
        case 1 : // retrait
            if ($type_compte == 1) { // compte courant
                if (($solde + $compte->decouvert) < $montant) {
                    return "Insufficient balance";
                }
            } else {// compte épargne
                if ($solde < $montant) {
                    return "Insufficient balance";
                }
            }
            $compteModifier["solde"] = $solde - $montant;
            break;
        case 2 : // versement
            $compteModifier["solde"] = $solde + $montant;
            break;
    }

    $current_date = date('Y-m-d H:i:s');
    $code_operation =  uniqid("op", true); //numero unique
    try {
        $db->beginTransaction();
        update_compte($compteModifier);
        $QUERY = "INSERT INTO operations (id_type_operation,montant,date_operation,id_compte,code) VALUES (:id_type_operation,:montant,:date_operation,:id_compte,:code)";
        $prepareStatement = $db->prepare($QUERY);
        $prepareStatement->bindParam(":id_type_operation", $type);
        $prepareStatement->bindParam(":montant", $montant);
        $prepareStatement->bindParam(":date_operation", $current_date);
        $prepareStatement->bindParam(":id_compte", $id_compte);
        $prepareStatement->bindParam(":code",$code_operation);
        $prepareStatement->execute();
        $id_operation = get_last_created_operation()->id;
        $QUERY = "INSERT INTO compte_has_operations (id_compte,id_operation) VALUES ($id_compte,$id_operation)";
        $prepareStatement = $db->prepare($QUERY);
        $prepareStatement->execute();
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return $e->getMessage();
    }
}

function find_operations($type){
    $db = $GLOBALS["db"];
    $QUERY = "SELECT * FROM operations ORDER  BY id DESC";
    $operations = $db->query($QUERY)->fetchall(PDO::FETCH_OBJ);
    $data = array();
    foreach ($operations as $operation){
        $record = (array)$operation;
        if($record["id_type_operation"]==$type){
            unset($record["id_type_operation"]);
            $compte = (array)find_compte_by_id($record["id_compte"]);
            if($compte["id_type_compte"] == 1){
                $compte["type"] = "courant";
                unset($compte["taux"]);
            }else{
                $compte["type"] = "épargne";
                unset($compte["decouvert"]);
            }
            unset($compte["id_type_compte"]);
            unset($compte["id_client"]);
            $record["compte"] = $compte;
            unset($record["id_compte"]);
            array_push($data,$record);
        }

    }

    return $data;
}

/**
 * @param $type
 * @param $code
 * @return array|void
 */
function find_operation_by_code($type,$code){
    $db = $GLOBALS["db"];
    $QUERY = "SELECT * FROM operations WHERE code='$code' ORDER  BY id DESC";
    $operations = $db->query($QUERY)->fetchall(PDO::FETCH_OBJ);
    $data = array();
    foreach ($operations as $operation){
        $record = (array)$operation;
        if($record["id_type_operation"]==$type){
            unset($record["id_type_operation"]);
            $compte = (array)find_compte_by_id($record["id_compte"]);
            if($compte["id_type_compte"] == 1){
                $compte["type"] = "courant";
                unset($compte["taux"]);
            }else{
                $compte["type"] = "épargne";
                unset($compte["decouvert"]);
            }
            unset($compte["id_type_compte"]);
            unset($compte["id_client"]);
            $record["compte"] = $compte;
            unset($record["id_compte"]);
            array_push($data,$record);
        }

        return $data;
    }
}

/**
 * @param $type
 * @param $id_compte
 * @return array|void
 */
function find_operation_by_id_compte($type,$id_compte){
    $db = $GLOBALS["db"];
    $QUERY = "SELECT * FROM operations WHERE id_compte='$id_compte' ORDER  BY id DESC";
    $operations = $db->query($QUERY)->fetchall(PDO::FETCH_OBJ);
    $data = array();
    foreach ($operations as $operation){
        $record = (array)$operation;
        if($record["id_type_operation"]==$type){
            unset($record["id_type_operation"]);
            $compte = (array)find_compte_by_id($record["id_compte"]);
            if($compte["id_type_compte"] == 1){
                $compte["type"] = "courant";
                unset($compte["taux"]);
            }else{
                $compte["type"] = "épargne";
                unset($compte["decouvert"]);
            }
            unset($compte["id_type_compte"]);
            unset($compte["id_client"]);
            $record["compte"] = $compte;
            unset($record["id_compte"]);
            array_push($data,$record);
        }

        return $data;
    }
}

/**
 * @param $id_compte
 * @return mixed
 */
function simple_select_operation_by_id_compte($id_compte){
    $db = $GLOBALS['db'];
    $QUERY = "SELECT DISTINCT o.* FROM operations o,compte_has_operations c WHERE c.id_operation = o.id AND c.id_compte ='$id_compte' ORDER  BY c.id_compte";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    return $records;
}


function create_operation_without_transaction($type,$data){
    $montant_min = 1000;
    $db = $GLOBALS["db"];
    if (!isset($data["montant"])) {
        return "Amount is required !";
    } else {
        if ($data["montant"] < $montant_min) {
            return "Amount must be great than $montant_min";
        }
        $montant = $data["montant"];
    }

    if (!isset($data["compte"])) {
        return "Account is required !";
    } else {
        if (empty($data["compte"])) return "Account can't be null";
        $compte = find_compte($data["compte"]);
        $compteModifier = (array)$compte;
        $id_compte = $compte->id;
        $type_compte = $compte->id_type_compte;
        $solde = $compte->solde;
        $statut = $compte->statut;
    }

    if($statut != 'actif' ) {
        return "Compte not activated";
        exit();
    }
    if ($type != 1 && $type != 2) {
        return "Type of Operation Not Found";
    }

    switch ((int)$type) {
        case 1 : // retrait
            if ($type_compte == 1) { // compte courant
                if (($solde + $compte->decouvert) < $montant) {
                    return "Insufficient balance";
                }
            } else {// compte épargne
                if ($solde < $montant) {
                    return "Insufficient balance";
                }
            }
            $compteModifier["solde"] = $solde - $montant;
            break;
        case 2 : // versement
            $compteModifier["solde"] = $solde + $montant;
            break;
    }

    $current_date = date('Y-m-d H:i:s');
    $code_operation =  uniqid("op", true); //numero unique
    try {
        update_compte($compteModifier);
        $QUERY = "INSERT INTO operations (id_type_operation,montant,date_operation,id_compte,code) VALUES (:id_type_operation,:montant,:date_operation,:id_compte,:code)";
        $prepareStatement = $db->prepare($QUERY);
        $prepareStatement->bindParam(":id_type_operation", $type);
        $prepareStatement->bindParam(":montant", $montant);
        $prepareStatement->bindParam(":date_operation", $current_date);
        $prepareStatement->bindParam(":id_compte", $id_compte);
        $prepareStatement->bindParam(":code",$code_operation);
        $prepareStatement->execute();
        $id_operation = get_last_created_operation()->id;
        $QUERY = "INSERT INTO compte_has_operations (id_compte,id_operation) VALUES ($id_compte,$id_operation)";
        $prepareStatement = $db->prepare($QUERY);
        $prepareStatement->execute();
        return true;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
 * @return mixed
 */
function get_last_created_operation()
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM operations ORDER  BY id DESC  LIMIT 1";
    $record = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    return $record[0];
}

