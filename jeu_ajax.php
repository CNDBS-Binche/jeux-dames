<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { exit('Accès refusé'); }
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// 1. ENREGISTRER UN COUP JOUÉ
if ($action == 'jouer' && isset($_POST['match_id'])) {
    $matchId = intval($_POST['match_id']);
    $depart = $_POST['depart'];
    $arrivee = $_POST['arrivee'];
    $couleur = $_POST['couleur']; // 'blanc' ou 'noir'

    // Compter le nombre de coups actuels pour générer le numéro du coup suivant
    $reqCoup = $bdd->prepare('SELECT COUNT(*) FROM mouvements_jcj WHERE match_id = ?');
    $reqCoup->execute([$matchId]);
    $numCoup = $reqCoup->fetchColumn() + 1;

    $ins = $bdd->prepare('INSERT INTO mouvements_jcj (match_id, num_coup, couleur, case_depart, case_arrivee) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$matchId, $numCoup, $couleur, $depart, $arrivee]);

    echo json_encode(['statut' => 'succes']);
    exit;
}

// 2. VÉRIFIER SI L'ADVERSAIRE A JOUÉ (Récupérer le tout dernier coup)
if ($action == 'charger_dernier_coup' && isset($_GET['match_id'])) {
    $matchId = intval($_GET['match_id']);

    $req = $bdd->prepare('SELECT couleur, case_depart, case_arrivee, num_coup FROM mouvements_jcj WHERE match_id = ? ORDER BY num_coup DESC LIMIT 1');
    $req->execute([$matchId]);
    $dernierCoup = $req->fetch(PDO::FETCH_ASSOC);

    if ($dernierCoup) {
        echo json_encode($dernierCoup);
    } else {
        echo json_encode(['num_coup' => 0]); // Aucun coup joué encore
    }
    exit;
}
?>