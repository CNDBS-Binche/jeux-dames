<?php
// --- CONFIGURATION DE LA BASE DE DONNÉES ---
$host = 'localhost';        // Serveur local
$dbname = 'maxcore981';   // Nom de la base que tu as créée en SQL
$username = 'root';         // Utilisateur par défaut sur Windows (WAMP/XAMPP)
$password = '';             // Mot de passe vide par défaut sur Windows

/* Note pour Mac (MAMP) : 
Si tu es sur Mac, le mot de passe est souvent 'root' au lieu de vide.
*/

try {
    // Connexion à MySQL avec PDO
    // On ajoute le charset utf8 pour que les accents s'affichent bien
    $bdd = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // On active la gestion des erreurs pour voir ce qui ne va pas pendant le développement
    $bdd->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Si la connexion échoue, on affiche l'erreur proprement
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>