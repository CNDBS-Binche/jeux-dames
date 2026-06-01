<?php
session_start();

// Inclusion de ta configuration OVH existante
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { 
    exit('Accès refusé'); 
}

$action = $_GET['action'] ?? '';

// ACTION A : Enregistrer un message dans le chat
if ($action == 'envoyer' && isset($_POST['message'])) {
    $msg = trim($_POST['message']);
    if (!empty($msg)) {
        // $bdd provient de ton fichier config.php
        $ins = $bdd->prepare('INSERT INTO chat_global (user_id, message) VALUES (?, ?)');
        $ins->execute([$_SESSION['user_id'], htmlspecialchars($msg)]);
    }
    exit;
}

// ACTION B : Récupérer les messages (avec pseudos et tags de clan)
if ($action == 'recuperer') {
    $req = $bdd->query('
        SELECT c.message, c.date_envoi, u.pseudo, cl.tag 
        FROM chat_global c 
        JOIN utilisateurs u ON c.user_id = u.id 
        LEFT JOIN clans cl ON u.clan_id = cl.id 
        ORDER BY c.date_envoi DESC LIMIT 30
    ');
    
    $messages = array_reverse($req->fetchAll(PDO::FETCH_ASSOC));

    foreach ($messages as $msg) {
        $heure = date('H:i', strtotime($msg['date_envoi']));
        // Si le joueur a un clan, on affiche son tag en jaune
        $clanTag = $msg['tag'] ? "<span style='color:#f1c40f'>[".$msg['tag']."]</span> " : "";
        
        echo "<p><strong>[$heure] $clanTag" . htmlspecialchars($msg['username']) . " :</strong> " . htmlspecialchars($msg['message']) . "</p>";
    }
    exit;
}
?>