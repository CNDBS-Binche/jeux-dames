<?php
// jcj_ajax.php — AJAX pour les parties Joueur contre Joueur (Version Fluide Long-Polling)
declare(strict_types=1);
session_start();
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['statut' => 'erreur', 'message' => 'Accès refusé']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';

// Configuration globale du Long-Polling (15 secondes max)
$maxAttente = 15;

// ── 1. Envoyer un défi ────────────────────────────────────────────────────────
if ($action === 'defier' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $idAmi = (int)($_POST['id_ami'] ?? 0);
    if ($idAmi === $userId) {
        echo json_encode(['statut' => 'erreur', 'message' => 'Vous ne pouvez pas vous défier vous-même']);
        exit;
    }

    // Annuler les anciens défis en attente entre ces deux joueurs
    $delete = $bdd->prepare("
        DELETE FROM parties_jcj
        WHERE statut = 'en_attente'
          AND ((id_challengeur = ? AND id_defie = ?)
            OR (id_challengeur = ? AND id_defie = ?))
    ");
    $delete->execute([$userId, $idAmi, $idAmi, $userId]);

    $ins = $bdd->prepare('INSERT INTO parties_jcj (id_challengeur, id_defie, statut) VALUES (?, ?, "en_attente")');
    $ins->execute([$userId, $idAmi]);
    echo json_encode(['statut' => 'succes', 'match_id' => (int)$bdd->lastInsertId()]);
    exit;
}

// ── 2. Vérifier les défis reçus / acceptés (LONG-POLLING) ────────────────────
if ($action === 'verifier_defis') {
    // On libère la session en écriture pour que l'utilisateur puisse naviguer ailleurs en même temps
    session_write_close(); 

    $debut = time();
    while ((time() - $debut) < $maxAttente) {
        
        // A. Un défi nous est envoyé ?
        $checkRecu = $bdd->prepare('
            SELECT p.id, u.pseudo
            FROM parties_jcj p
            JOIN utilisateurs u ON p.id_challengeur = u.id
            WHERE p.id_defie = ? AND p.statut = "en_attente"
            LIMIT 1
        ');
        $checkRecu->execute([$userId]);
        $defiRecu = $checkRecu->fetch();
        
        if ($defiRecu) {
            echo json_encode(['type' => 'recu', 'match_id' => $defiRecu['id'], 'adversaire' => $defiRecu['pseudo']]);
            exit;
        }

        // B. Notre défi a-t-il été accepté ?
        $checkLance = $bdd->prepare('SELECT id FROM parties_jcj WHERE id_challengeur = ? AND statut = "accepte" LIMIT 1');
        $checkLance->execute([$userId]);
        $defiAccepte = $checkLance->fetch();
        
        if ($defiAccepte) {
            // Reconnexion rapide pour changer le statut à "en_cours"
            $bdd->prepare('UPDATE parties_jcj SET statut = "en_cours" WHERE id = ?')->execute([$defiAccepte['id']]);
            echo json_encode(['type' => 'lance_accepte', 'match_id' => $defiAccepte['id']]);
            exit;
        }

        // Rien de neuf ? On attend un quart de seconde avant la prochaine vérification
        usleep(250000);
    }

    // Passé 15s, on dit au JS qu'il n'y a rien pour qu'il relance sa boucle proprement
    echo json_encode(['type' => 'aucun']);
    exit;
}

// ── 3. Répondre à un défi (accepter / refuser) ────────────────────────────────
if ($action === 'repondre' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId  = (int)($_POST['match_id']  ?? 0);
    $decision = ($_POST['decision'] ?? '') === 'accepte' ? 'accepte' : 'refuse';

    $upd = $bdd->prepare("
        UPDATE parties_jcj
        SET statut = ?
        WHERE id = ? AND id_defie = ? AND statut = 'en_attente'
    ");
    $upd->execute([$decision, $matchId, $userId]);

    if ($upd->rowCount() === 0) {
        echo json_encode(['statut' => 'erreur', 'message' => 'Défi déjà traité ou introuvable']);
    } else {
        echo json_encode(['statut' => 'succes']);
    }
    exit;
}

// ── 4. Se déclarer prêt ───────────────────────────────────────────────────────
if ($action === 'se_declarer_pret' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId = (int)($_POST['match_id'] ?? 0);
    if ($matchId === 0) {
        echo json_encode(['statut' => 'erreur', 'message' => 'match_id manquant']);
        exit;
    }

    $req = $bdd->prepare("
        UPDATE parties_jcj
        SET
            pret_challengeur = CASE WHEN id_challengeur = ? THEN 1 ELSE pret_challengeur END,
            pret_defie       = CASE WHEN id_defie       = ? THEN 1 ELSE pret_defie       END
        WHERE id = ?
          AND (id_challengeur = ? OR id_defie = ?)
    ");
    $req->execute([$userId, $userId, $matchId, $userId, $userId]);

    if ($req->rowCount() === 0) {
        echo json_encode(['statut' => 'erreur', 'message' => 'Partie introuvable ou accès non autorisé']);
    } else {
        echo json_encode(['statut' => 'succes']);
    }
    exit;
}

// ── 5. Vérifier l'état des prêts ──────────────────────────────────────────────
if ($action === 'verifier_prets') {
    $matchId = (int)($_GET['match_id'] ?? 0);

    $req = $bdd->prepare("
        SELECT pret_challengeur, pret_defie
        FROM parties_jcj
        WHERE id = ? AND (id_challengeur = ? OR id_defie = ?)
    ");
    $req->execute([$matchId, $userId, $userId]);
    $etat = $req->fetch();

    if ($etat) {
        echo json_encode(['blanc' => (int)$etat['pret_challengeur'] === 1, 'noir' => (int)$etat['pret_defie'] === 1]);
    } else {
        echo json_encode(['blanc' => false, 'noir' => false]);
    }
    exit;
}

// ── 6. Enregistrer un coup ────────────────────────────────────────────────────
if ($action === 'jouer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchId = (int)($_POST['match_id'] ?? 0);
    $depart  = trim($_POST['depart']   ?? '');
    $arrivee = trim($_POST['arrivee']  ?? '');
    $couleur = trim($_POST['couleur']  ?? '');

    // Validation basique du format "ligne,colonne"
    if (!$matchId || !preg_match('/^\d+,\d+$/', $depart) || !preg_match('/^\d+,\d+$/', $arrivee) || !in_array($couleur, ['blanc', 'noir'])) {
        echo json_encode(['statut' => 'erreur', 'message' => 'Données invalides']);
        exit;
    }

    // Vérifier que ce joueur est bien dans cette partie
    $check = $bdd->prepare('SELECT id FROM parties_jcj WHERE id = ? AND (id_challengeur = ? OR id_defie = ?)');
    $check->execute([$matchId, $userId, $userId]);
    if (!$check->fetch()) {
        http_response_code(403);
        echo json_encode(['statut' => 'erreur', 'message' => 'Accès non autorisé']);
        exit;
    }

    $req = $bdd->prepare('SELECT COALESCE(MAX(num_coup), 0) + 1 FROM mouvements_jcj WHERE match_id = ?');
    $req->execute([$matchId]);
    $numCoup = (int)$req->fetchColumn();

    $ins = $bdd->prepare('INSERT INTO mouvements_jcj (match_id, num_coup, couleur, case_depart, case_arrivee) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$matchId, $numCoup, $couleur, $depart, $arrivee]);

    echo json_encode(['statut' => 'succes', 'num_coup' => $numCoup]);
    exit;
}

// ── 7. Charger le dernier coup (LONG-POLLING) ────────────────────────────────
if ($action === 'charger_dernier_coup') {
    $matchId = (int)($_GET['match_id'] ?? 0);
    $dernierCoupLocal = (int)($_GET['dernier_compteur'] ?? 0); // Requis pour savoir s'il y a du nouveau

    session_write_close();

    $debut = time();
    while ((time() - $debut) < $maxAttente) {
        $req = $bdd->prepare('SELECT num_coup, couleur, case_depart, case_arrivee FROM mouvements_jcj WHERE match_id = ? ORDER BY num_coup DESC LIMIT 1');
        $req->execute([$matchId]);
        $coup = $req->fetch();

        // Si la base contient un coup plus récent que celui affiché sur le PC du joueur
        if ($coup && (int)$coup['num_coup'] > $dernierCoupLocal) {
            echo json_encode($coup);
            exit;
        }

        usleep(250000);
    }

    // Timeout après 15 secondes sans action
    echo json_encode(['num_coup' => $dernierCoupLocal, 'statut' => 'timeout']);
    exit;
}

http_response_code(404);
echo json_encode(['statut' => 'erreur', 'message' => 'Action inconnue']);
exit;