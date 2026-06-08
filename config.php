<?php
// ============================================================
// config.php — Connexion PDO Directe
// ============================================================

$host     = 'maxcore981.mysql.db';
$dbname   = 'maxcore981';
$username = 'maxcore981';
$password = 'Antonio64';

// Connexion brute, sans blocage try/catch
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