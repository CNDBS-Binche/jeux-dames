<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];

/* ==========================================================================
   TRAITEMENT DES ACTIONS (Ajouter, Accepter, Refuser, Retirer)
   ========================================================================== */
$message = "";
$messageType = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. AJOUTER UN AMI
    if ($action === 'ajouter' && !empty($_POST['pseudo_recherche'])) {
        $pseudoRecherche = trim($_POST['pseudo_recherche']);
        
        $reqUser = $bdd->prepare('SELECT id FROM utilisateurs WHERE pseudo = ?');
        $reqUser->execute([$pseudoRecherche]);
        $cible = $reqUser->fetch();

        if (!$cible) {
            $message = "Ce joueur n'existe pas.";
            $messageType = "danger";
        } elseif ($cible['id'] == $userId) {
            $message = "Vous ne pouvez pas vous ajouter vous-même.";
            $messageType = "danger";
        } else {
            $reqVerif = $bdd->prepare('SELECT id, statut FROM amis WHERE (user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)');
            $reqVerif->execute([$userId, $cible['id'], $cible['id'], $userId]);
            $relation = $reqVerif->fetch();

            if ($relation) {
                if ($relation['statut'] === 'en_attente') {
                    $message = "Une invitation est déjà en attente avec ce joueur.";
                } else {
                    $message = "Vous êtes déjà ami avec ce joueur.";
                }
                $messageType = "danger";
            } else {
                $reqIns = $bdd->prepare('INSERT INTO amis (user_id_1, user_id_2, statut) VALUES (?, ?, "en_attente")');
                $reqIns->execute([$userId, $cible['id']]);
                $message = "Invitation envoyée avec succès à " . htmlspecialchars($pseudoRecherche) . " !";
                $messageType = "success";
            }
        }
    }

    // 2. ACCEPTER UNE DEMANDE
    if ($action === 'accepter' && isset($_POST['relation_id'])) {
        $relationId = (int)$_POST['relation_id']; // Correction ici : $_POST au lieu de $POST
        $reqUp = $bdd->prepare('UPDATE amis SET statut = "accepte" WHERE id = ? AND user_id_2 = ?');
        $reqUp->execute([$relationId, $userId]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // 3. REFUSER UNE DEMANDE OU SUPPRIMER UN AMI
    if ($action === 'supprimer' && isset($_POST['relation_id'])) {
        $relationId = (int)$_POST['relation_id']; // Correction ici : $_POST au lieu de $POST
        $reqDel = $bdd->prepare('DELETE FROM amis WHERE id = ? AND (user_id_1 = ? OR user_id_2 = ?)');
        $reqDel->execute([$relationId, $userId, $userId]);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

/* ==========================================================================
   RÉCUPÉRATION DES DONNÉES POUR L'AFFICHAGE
   ========================================================================== */
$reqDemandes = $bdd->prepare('
    SELECT a.id as relation_id, u.pseudo 
    FROM amis a 
    JOIN utilisateurs u ON a.user_id_1 = u.id 
    WHERE a.user_id_2 = ? AND a.statut = "en_attente"
');
$reqDemandes->execute([$userId]);
$demandes = $reqDemandes->fetchAll(PDO::FETCH_ASSOC);

$reqAmis = $bdd->prepare('
    SELECT a.id as relation_id, 
           IF(a.user_id_1 = ?, u2.pseudo, u1.pseudo) as pseudo_ami
    FROM amis a
    JOIN utilisateurs u1 ON a.user_id_1 = u1.id
    JOIN utilisateurs u2 ON a.user_id_2 = u2.id
    WHERE (a.user_id_1 = ? OR a.user_id_2 = ?) AND a.statut = "accepte"
');
$reqAmis->execute([$userId, $userId, $userId]);
$amis = $reqAmis->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes Amis - Jeu de Dames</title>
    <style>
        /* ==========================================================================
           1. LE FOND D'ÉCRAN BRUN BOISÉ ET SON QUADRILLAGE ANIMÉ
           ========================================================================== */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5;
            overflow-x: hidden;
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            width: 200%;
            height: 200%;
            background-image: 
                linear-gradient(45deg, rgba(0,0,0,0.1) 25%, transparent 25%), 
                linear-gradient(-45deg, rgba(0,0,0,0.1) 25%, transparent 25%), 
                linear-gradient(45deg, transparent 75%, rgba(0,0,0,0.1) 75%), 
                linear-gradient(-45deg, transparent 75%, rgba(0,0,0,0.1) 75%);
            background-size: 100px 100px;
            z-index: 0;
            animation: move 60s linear infinite;
        }

        @keyframes move {
            from { transform: translate(-25%, -25%); }
            to { transform: translate(0, 0); }
        }

        /* ==========================================================================
           2. BARRE LATÉRALE DE NAVIGATION (Marron sombre)
           ========================================================================== */
        .sidebar {
            width: 240px;
            background-color: rgba(27, 18, 11, 0.9);
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            padding: 20px 10px;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            position: fixed;
            height: 100vh;
            box-sizing: border-box;
            z-index: 10;
        }

        .sidebar-brand {
            font-size: 22px;
            font-weight: bold;
            color: #fff;
            margin-bottom: 30px;
            padding-left: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
            flex-grow: 1;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #c4b49c;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar-link:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left: 4px solid #81b64c;
            padding-left: 11px;
        }

        /* ==========================================================================
           3. CONTENU PRINCIPAL & CARTES FLOTTANTES MARRONS
           ========================================================================== */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            max-width: 1200px;
            width: calc(100% - 240px);
            z-index: 1;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.6fr 1.1fr;
            gap: 30px;
        }

        @media (max-width: 950px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .left-column, .right-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .card {
            background-color: rgba(33, 21, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
        }

        .card h2 {
            margin-top: 0;
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card p {
            margin: 0 0 20px 0;
            font-size: 14px;
            line-height: 1.6;
            color: #c4b49c;
        }

        /* ==========================================================================
           4. BOUTONS STYLE CHESS.COM & FORMULAIRES
           ========================================================================== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            border: none;
            transition: filter 0.2s, transform 0.1s;
            cursor: pointer;
            box-shadow: 0 4px 0 rgba(0,0,0,0.2);
        }

        .btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 0 rgba(0,0,0,0.2);
        }

        .btn-primary {
            background-color: #5d3a1a;
            border-bottom: 3px solid #3e2510;
        }
        .btn-primary:hover {
            background-color: #7a4a28; 
        }

        .btn-play {
            background-color: #81b64c;
            border-bottom: 4px solid #68943b;
            font-size: 16px;
            text-transform: uppercase;
            padding: 14px 24px;
            width: 100%;
        }
        .btn-play:hover { 
            background-color: #95cc5a; 
        }

        .btn-danger {
            background-color: #c0392b;
            border-bottom: 3px solid #962d22;
        }
        .btn-danger:hover {
            background-color: #e74c3c;
        }

        .btn-logout {
            background-color: rgba(62, 37, 16, 0.5);
            color: #e74c3c;
            margin-top: auto;
        }
        .sidebar-link.btn-logout:hover { 
            background-color: #c0392b; color: #fff; 
        }

        .search-box {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .search-box input {
            flex: 1;
            padding: 12px;
            border: 1px solid #5d3a1a;
            border-radius: 6px;
            background-color: rgba(0, 0, 0, 0.4);
            color: #fff;
            font-size: 14px;
            outline: none;
        }

        .search-box input:focus {
            border-color: #81b64c;
        }

        .liste-joueurs {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .liste-joueurs li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.2);
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.02);
        }

        .empty-state {
            color: #a8947a;
            font-style: italic;
            text-align: center;
            padding: 15px 0;
            font-size: 14px;
        }

        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            font-weight: 600;
        }
        .alert-success { background-color: rgba(129, 182, 76, 0.2); color: #81b64c; border: 1px solid rgba(129, 182, 76, 0.4); }
        .alert-danger { background-color: rgba(192, 57, 43, 0.2); color: #e74c3c; border: 1px solid rgba(192, 57, 43, 0.4); }

        .form-inline {
            display: inline;
            margin: 0;
        }
        .actions-wrapper {
            display: flex;
            gap: 8px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            ⚪ Jeu de Dames
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link">🏠 Accueil</a>
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link active">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link active">👥 Amis</a>
            <a href="clans.php" class="sidebar-link active">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        <h1 style="color: #fff; margin-top: 0; margin-bottom: 30px; font-weight: 600;">Gestion de la Communauté</h1>

        <div class="dashboard-grid">
            
            <div class="left-column">
                <div class="card">
                    <h2>🤝 Ajouter un ami</h2>
                    <p>Recherchez un joueur à l'aide de son pseudonyme exact pour l'ajouter à votre liste de contacts.</p>
                    
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-<?php echo $messageType; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="form-inline">
                        <input type="hidden" name="action" value="ajouter">
                        <div class="search-box">
                            <input type="text" name="pseudo_recherche" placeholder="Entrez le pseudo du joueur..." required>
                            <button type="submit" class="btn btn-play" style="width: auto;">Inviter</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h2>👥 Mes Amis (<?php echo count($amis); ?>)</h2>
                    <ul class="liste-joueurs">
                        <?php if(empty($amis)): ?>
                            <p class="empty-state">Vous n'avez pas encore d'amis dans votre liste.</p>
                        <?php else: ?>
                            <?php foreach($amis as $a): ?>
                                <li>
                                    <span>🟢 <strong style="color:#fff;"><?php echo htmlspecialchars($a['pseudo_ami']); ?></strong></span>
                                    <div class="actions-wrapper">
                                        <button class="btn btn-primary" style="padding: 8px 14px; font-size: 13px;" onclick="lancerDuel()">⚔️ Duel</button>
                                        
                                        <form method="POST" action="" class="form-inline">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="relation_id" value="<?php echo $a['relation_id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 8px 14px; font-size: 13px;">Retirer</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

            <div class="right-column">
                <div class="card">
                    <h2>📩 Demandes reçues (<?php echo count($demandes); ?>)</h2>
                    <ul class="liste-joueurs">
                        <?php if(empty($demandes)): ?>
                            <p class="empty-state">Aucune invitation en attente.</p>
                        <?php else: ?>
                            <?php foreach($demandes as $d): ?>
                                <li style="flex-direction: column; align-items: flex-start; gap: 10px;">
                                    <span style="font-size: 14px;"><strong style="color: #fff;"><?php echo htmlspecialchars($d['pseudo']); ?></strong> veut être votre ami</span>
                                    <div class="actions-wrapper" style="width: 100%;">
                                        
                                        <form method="POST" action="" class="form-inline" style="flex: 1;">
                                            <input type="hidden" name="action" value="accepter">
                                            <input type="hidden" name="relation_id" value="<?php echo $d['relation_id']; ?>">
                                            <button type="submit" class="btn btn-play" style="padding: 6px 12px; font-size: 13px; width: 100%;">Accepter</button>
                                        </form>

                                        <form method="POST" action="" class="form-inline" style="flex: 1;">
                                            <input type="hidden" name="action" value="supprimer">
                                            <input type="hidden" name="relation_id" value="<?php echo $d['relation_id']; ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 13px; width: 100%;">Refuser</button>
                                        </form>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>

        </div>
    </div>

<script>
function lancerDuel() {
    alert("Défi envoyé ! Préparez vos pions ⚔️");
}
</script>
</body>
</html>