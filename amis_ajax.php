<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit('Accès refusé');
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// 1. ENVOYER UNE DEMANDE D'AMI
if ($action == 'demander' && isset($_POST['pseudo_ami'])) {
    $pseudoAmi = trim($_POST['pseudo_ami']);
    
    // Trouver l'ID de l'ami par son pseudo
    $req = $bdd->prepare('SELECT id FROM utilisateurs WHERE pseudo = ?');
    $req->execute([$pseudoAmi]);
    $ami = $req->fetch();
    
    if (!$ami) {
        exit('Joueur introuvable.');
    }
    
    if ($ami['id'] == $userId) {
        exit('Vous cannot pas vous ajouter vous-même !');
    }
    
    // Vérifier si une relation existe déjà
    $verif = $bdd->prepare('SELECT id FROM amis WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)');
    $verif->execute([$userId, $ami['id'], $ami['id'], $userId]);
    
    if ($verif->fetch()) {
        exit('Une demande existe déjà ou vous êtes déjà amis.');
    }
    
    // Insérer la demande (user_id_1 est l'expéditeur, user_id_2 le destinataire)
    $ins = $bdd->prepare('INSERT INTO amis (user_id_1, user_id_2, statut) VALUES (?, ?, "en_attente")');
    $ins->execute([$userId, $ami['id']]);
    exit('succes');
}

// 2. ACCEPTER UNE DEMANDE
if ($action == 'accepter' && isset($_POST['relation_id'])) {
    $relId = intval($_POST['relation_id']);
    // Sécurité : vérifier que le destinataire est bien l'utilisateur connecté
    $upd = $bdd->prepare('UPDATE amis SET statut = "accepte" WHERE id = ? AND user_id_2 = ?');
    $upd->execute([$relId, $userId]);
    exit('succes');
}

// 3. REFUSER OU SUPPRIMER UNE RELATION
if ($action == 'supprimer' && isset($_POST['relation_id'])) {
    $relId = intval($_POST['relation_id']);
    // Permet de supprimer si on est l'un des deux participants
    $del = $bdd->prepare('DELETE FROM amis WHERE id = ? AND (user_id_1 = ? OR user_id_2 = ?)');
    $del->execute([$relId, $userId, $userId]);
    exit('succes');
}
?>