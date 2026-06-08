<?php
// ============================================================
// config.php — Connexion PDO sécurisée
// IMPORTANT : Ne jamais committer ce fichier avec de vraies
// credentials. Utiliser des variables d'environnement en prod.
// ============================================================

$host     = getenv('DB_HOST')     ?: 'maxcore981.mysql.db';
$dbname   = getenv('DB_NAME')     ?: 'maxcore981';
$username = getenv('DB_USER')     ?: 'maxcore981';
$password = getenv('DB_PASSWORD') ?: '';   // ← NE PAS mettre le mot de passe ici

try {
    $bdd = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // vraies requêtes préparées
        ]
    );
} catch (PDOException $e) {
    // En production : logger l'erreur, ne jamais l'afficher
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(503);
    die('Service temporairement indisponible.');
}
