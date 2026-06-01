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

// ACTION B : Récupérer les messages
if ($action == 'recuperer') {
    try {
        // LEFT JOIN est crucial ici : il permet d'afficher le message même si cl.tag n'existe pas encore !
        $req = $bdd->query('
            SELECT c.message, c.date_envoi, u.pseudo, cl.tag 
            FROM chat_global c 
            INNER JOIN utilisateurs u ON c.user_id = u.id 
            LEFT JOIN clans cl ON u.clan_id = cl.id 
            ORDER BY c.date_envoi DESC LIMIT 30
        ');
        
        $messages = $req->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($messages)) {
            echo "<p style='color:#a1887f; text-align:center; font-style:italic; padding-top:20px;'>Le chat est vide. Écrivez le premier message !</p>";
            exit;
        }
        
        // On remet les messages dans le bon sens chronologique
        $messages = array_reverse($messages);

        foreach ($messages as $msg) {
            $heure = date('H:i', strtotime($msg['date_envoi']));
            $clanTag = (!empty($msg['tag'])) ? "<span style='color:#f1c40f'>[".$msg['tag']."]</span> " : "";
            
            echo "<p style='margin: 4px 0;'><strong>[$heure] $clanTag" . htmlspecialchars($msg['pseudo']) . " :</strong> " . htmlspecialchars($msg['message']) . "</p>";
        }
    } catch (PDOException $e) {
        // En cas d'erreur SQL (colonne manquante, table mal nommée), elle s'affichera directement dans le chat
        echo "<p style='color:#e74c3c;'>⚠️ Erreur SQL : " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    exit;
}
?>