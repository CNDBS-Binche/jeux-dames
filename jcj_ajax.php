<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) { exit('Accès refusé'); }
$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// 1. ENVOYER UN DÉFI
if ($action == 'defier' && isset($_POST['id_ami'])) {
    $idAmi = intval($_POST['id_ami']);
    
    // Annuler les anciens défis en attente entre ces deux joueurs pour éviter les doublons
    $bdd->prepare('DELETE FROM parties_jcj WHERE id_challengeur = ? AND id_defie = ? AND statut = "en_attente"')->execute([$userId, $idAmi]);

    // Insérer le nouveau défi
    $ins = $bdd->prepare('INSERT INTO parties_jcj (id_challengeur, id_defie, statut) VALUES (?, ?, "en_attente")');
    $ins->execute([$userId, $idAmi]);
    
    echo json_encode(['statut' => 'succes', 'match_id' => $bdd->lastInsertId()]);
    exit;
}

// 2. VÉRIFIER SI ON REÇOIT UN DÉFI (Pour la pop-up) OU SI NOTRE DÉFI A ÉTÉ ACCÉPTÉ
if ($action == 'verifier_defis') {
    // A. Est-ce qu'on me défie ?
    $checkRecu = $bdd->prepare('SELECT p.id, u.pseudo FROM parties_jcj p JOIN utilisateurs u ON p.id_challengeur = u.id WHERE p.id_defie = ? AND p.statut = "en_attente" LIMIT 1');
    $checkRecu->execute([$userId]);
    $defiRecu = $checkRecu->fetch(PDO::FETCH_ASSOC);
    
    if ($defiRecu) {
        echo json_encode(['type' => 'recu', 'match_id' => $defiRecu['id'], 'adversaire' => $defiRecu['pseudo']]);
        exit;
    }

    // B. Est-ce qu'un défi que J'AI lancé a été accepté ?
    $checkLance = $bdd->prepare('SELECT id FROM parties_jcj WHERE id_challengeur = ? AND statut = "accepte" LIMIT 1');
    $checkLance->execute([$userId]);
    $defiAccepte = $checkLance->fetch(PDO::FETCH_ASSOC);

    if ($defiAccepte) {
        // On passe la partie "en_cours" pour ne pas boucler
        $bdd->prepare('UPDATE parties_jcj SET statut = "en_cours" WHERE id = ?')->execute([$defiAccepte['id']]);
        echo json_encode(['type' => 'lance_accepte', 'match_id' => $defiAccepte['id']]);
        exit;
    }

    echo json_encode(['type' => 'aucun']);
    exit;
}

// 3. ACCEPTER OU REFUSER UN DÉFI REÇU
if ($action == 'repondre' && isset($_POST['match_id']) && isset($_POST['decision'])) {
    $matchId = intval($_POST['match_id']);
    $decision = ($_POST['decision'] === 'accepte') ? 'accepte' : 'refuse';

    $upd = $bdd->prepare('UPDATE parties_jcj SET statut = ? WHERE id = ? AND id_defie = ?');
    $upd->execute([$decision, $matchId, $userId]);

    echo json_encode(['statut' => 'succes']);
    exit;
}
?>