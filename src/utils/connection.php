<?php
require __DIR__.'/../../vendor/autoload.php';
$racine = __DIR__.'/../../';
$dotenv = Dotenv\Dotenv::createImmutable($racine);
$envFile = $racine . '.env';
if (file_exists($envFile)) {
    $dotenv->load();
}
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT','SECRET_KEY','CLIENT_ID','CLIENT_SECRET']);

$host = $_SERVER["DB_HOST"];
$db_name = $_SERVER["DB_NAME"];
$db_user = $_SERVER["DB_USER"];
$db_pass = $_SERVER["DB_PASS"];
$db_port = $_SERVER["DB_PORT"];

try {

    $connStr = "mysql:host=$host;dbname=$db_name";

    $arrExtraParam= array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo = new PDO($connStr, $db_user, $db_pass, $arrExtraParam);
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->query("SET NAMES 'utf8'"); //au cas o� MYSQL_ATTR_INIT_COMMAND ne marche pas

    $GLOBALS['db'] = $pdo;

} catch (PDOException $e) {

    $msg = 'ERREUR PDO dans le fichier' . $e->getFile() . ' à la ligne ' . $e->getLine() . ' : ' . $e->getMessage(); //Message d'erreur

    die($msg); //Affichage du message d'erreur et arrrêt du processus
}