<?php

function create_virement($data)
{
    $data = check_input_virement($data);
    if (is_array($data)) {
        $code = uniqid("vir", true); //numero unique
        $numero_compte_depart = $data["compte_depart"];
        $numero_compte_arrivee = $data["compte_arrivee"];
        $montant = $data["montant"];

        try {
            $db = $GLOBALS["db"];
            $db->beginTransaction();
            //RETRAIT
            $data_compte_depart = array("montant" => $montant, "compte" => $numero_compte_depart);
            create_operation_without_transaction(1/** retrait*/, $data_compte_depart);
            //VERSEMENT
            $data_compte_arrivee = array("montant" => $montant, "compte" => $numero_compte_arrivee);
            create_operation_without_transaction(2/** versement*/, $data_compte_arrivee);

            //ENREGISTREMENT DU VIREMENT
            $QUERY = "INSERT INTO virements (id_compte_depart,id_compte_arrivee,montant,date_virement,code) VALUES (:id_compte_depart,:id_compte_arrivee,:montant,:date_virement,:code) ";
            $prepareStatement = $db->prepare($QUERY);
            $compte_depart = (array)find_compte($numero_compte_depart);
            $prepareStatement->bindParam(":id_compte_depart", $compte_depart["id"]);
            $compte_arrivee = (array)find_compte($numero_compte_arrivee);
            $prepareStatement->bindParam(":id_compte_arrivee", $compte_arrivee["id"]);
            $prepareStatement->bindParam(":montant", $montant);
            $prepareStatement->bindParam(":date_virement", date("Y-m-d H:i:s"));
            $prepareStatement->bindParam(":code", $code);
            $prepareStatement->execute();
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            return $e->getMessage();
        }
    } else {
        return $data;
    }
}

function find_virements()
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM virements ORDER  BY id DESC  LIMIT 1";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    $temp = array();
    foreach ($records as $record) {
        $record = (array)$record;
        $id_cp_dep = $record["id_compte_depart"];
        $id_cp_arr = $record["id_compte_arrivee"];
        $record["compte_depart"] = find_compte_by_id($id_cp_dep)->numero;
        $record["compte_arrivee"] = find_compte_by_id($id_cp_arr)->numero;
        unset($record["id_compte_depart"]);
        unset($record["id_compte_arrivee"]);
        unset($record["id"]);
        array_push($temp, $record);
    }
    return $temp;
}

function find_virement_by_code($code)
{
    $db = $GLOBALS['db'];
    $QUERY = "SELECT * FROM virements WHERE code='$code' ORDER  BY id DESC  LIMIT 1";
    $records = $db->query($QUERY)->fetchAll(PDO::FETCH_OBJ);
    $temp = array();
    foreach ($records as $record) {
        $record = (array)$record;
        $id_cp_dep = $record["id_compte_depart"];
        $id_cp_arr = $record["id_compte_arrivee"];
        $record["compte_depart"] = find_compte_by_id($id_cp_dep)->numero;
        $record["compte_arrivee"] = find_compte_by_id($id_cp_arr)->numero;
        unset($record["id_compte_depart"]);
        unset($record["id_compte_arrivee"]);
        unset($record["id"]);
        array_push($temp, $record);
    }
    return $temp[0];

}

function check_input_virement($data)
{
    if (!isset($data["compte_depart"])) return "compte_depart is required";
    if (!isset($data["compte_arrivee"])) return "compte_arrivee is required";
    if (!isset($data["montant"])) return "montant is required";
    return $data;
}