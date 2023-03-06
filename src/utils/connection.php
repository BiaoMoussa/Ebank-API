<?php

try {

    $connStr = "mysql:host=localhost;dbname=eBanque";

    $arrExtraParam= array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo = new PDO($connStr, 'root', '', $arrExtraParam);
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->query("SET NAMES 'utf8'"); //au cas o� MYSQL_ATTR_INIT_COMMAND ne marche pas

    $GLOBALS['db'] = $pdo;

} catch (PDOException $e) {

    $msg = 'ERREUR PDO dans le fichier' . $e->getFile() . ' à la ligne ' . $e->getLine() . ' : ' . $e->getMessage(); //Message d'erreur

    die($msg); //Affichage du message d'erreur et arrrêt du processus
}