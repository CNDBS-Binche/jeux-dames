<?php
// chat_ajax.php — AJAX pour le chat global
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['erreur' => 'Non connecté']);
    exit;
}

$action = $_GET['action'] ?? '';

// ACTION A : Envoyer un message
if ($action === 'envoyer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['message'] ?? '');
    if ($msg === '') {
        echo json_encode(['statut' => 'erreur', 'message' => 'Message vide']);
        exit;
    }
    if (mb_strlen($msg) > 500) {
        echo json_encode(['statut' => 'erreur', 'message' => 'Message trop long']);
        exit;
    }
    // Stocker le texte brut — l'échappement se fait à l'affichage
    $ins = $bdd->prepare('INSERT INTO chat_global (user_id, message) VALUES (?, ?)');
    $ins->execute([$_SESSION['user_id'], $msg]);
    echo json_encode(['statut' => 'succes']);
    exit;
}

// ACTION B : Récupérer les messages (retour JSON propre)
if ($action === 'recuperer') {
    $req = $bdd->query('
        SELECT c.message, c.date_envoi, u.pseudo
        FROM chat_global c
        INNER JOIN utilisateurs u ON c.user_id = u.id
        ORDER BY c.date_envoi DESC
        LIMIT 30
    ');
    $messages = array_reverse($req->fetchAll());

    // Renvoyer du JSON — l'affichage HTML est fait côté client
    $resultat = array_map(fn($m) => [
        'pseudo'     => $m['pseudo'],
        'heure'      => date('H:i', strtotime($m['date_envoi'])),
        'message'    => $m['message'],
    ], $messages);

    echo json_encode($resultat);
    exit;
}

http_response_code(400);
echo json_encode(['erreur' => 'Action inconnue']);
exit;
