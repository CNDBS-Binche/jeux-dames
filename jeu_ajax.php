<?php
// jeu_ajax.php — DÉPRÉCIÉ
// Ce fichier était un doublon de jcj_ajax.php.
// Toutes les requêtes doivent passer par jcj_ajax.php.
header('Location: jcj_ajax.php?' . http_build_query($_GET));
exit;
