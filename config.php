<?php
// ============================================================
// config.php — Connexion PDO Directe
// ============================================================

$host     = 'mysql-maximepcndbs.alwaysdata.net';
$dbname   = 'maximepcndbs_dames';
$username = 'maximepcndbs';
$password = '@3J8Ax3t9:y9:dlU26';

try {
    $bdd = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Si ça plante, on affiche l'erreur proprement en haut de page
    // sans casser le type de document du navigateur
    echo '<div style="background:#ffdddd; color:#aa0000; padding:15px; border:2px solid #cc0000; font-family:sans-serif; font-weight:bold; margin:20px;">';
    echo '❌ Erreur de connexion à la base de données :<br>';
    echo $e->getMessage();
    echo '</div>';
    exit; // On arrête le script proprement
}
