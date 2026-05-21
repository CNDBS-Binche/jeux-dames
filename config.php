<?php
$host = 'maxcore981.mysql.db';       
$dbname = 'maxcore981';   
$username = 'maxcore981';         
$password = 'Antonio64';

try {
    $bdd = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>