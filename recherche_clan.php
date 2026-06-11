<?php
session_start();
require_once 'config.php';

// 1. VÉRIFICATION DE LA SESSION
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];

// 2. GÉNÉRATION DU JETON CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "";

// 3. TRAITEMENT DE LA DEMANDE DE REJOINDRE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rejoindre') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Erreur de sécurité.";
        $messageType = "danger";
    } else {
        $clanId = (int)$_POST['clan_id'];
        
        // Logique SQL à insérer ici plus tard
        $message = "Demande envoyée avec succès au clan !";
        $messageType = "success";
    }
}

// 4. SIMULATION DES CLANS DISPONIBLES (Avec ajout d'une clé 'langue' pour la cohérence)
$clansListe = [
    ['id' => 1, 'nom' => 'Les Maîtres du Damier', 'drapeau' => '🇫🇷', 'langue' => 'Français', 'trophees' => 4520, 'membres' => 12, 'bio' => 'Objectif top mondial ! Aucun pion laissé au hasard.'],
    ['id' => 2, 'nom' => 'Blitz Faction', 'drapeau' => '🇧🇪', 'langue' => 'Français', 'trophees' => 3890, 'membres' => 8, 'bio' => 'Pour les amoureux des parties rapides et agressives.'],
    ['id' => 3, 'nom' => 'Strategia', 'drapeau' => '🇨🇦', 'langue' => 'Anglais/Français', 'trophees' => 2100, 'membres' => 4, 'bio' => 'Ici on apprend et on progresse ensemble calmement.']
];

if (!empty($_GET['search'])) {
    $search = strtolower(trim($_GET['search']));
    $clansListe = array_filter($clansListe, function($c) use ($search) {
        return str_contains(strtolower($c['nom']), $search);
    });
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rechercher un Clan - Jeu de Dames</title>
    <style>
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5;
            overflow: hidden;
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

        .sidebar-brand { font-size: 22px; font-weight: bold; color: #fff; margin-bottom: 30px; padding-left: 15px; display: flex; align-items: center; gap: 10px; }
        .sidebar-menu { display: flex; flex-direction: column; gap: 5px; flex-grow: 1; }
        .sidebar-link { display: flex; align-items: center; gap: 15px; color: #c4b49c; text-decoration: none; padding: 12px 15px; border-radius: 6px; font-weight: 600; transition: background 0.2s, color 0.2s; }
        .sidebar-link:hover { background-color: rgba(255, 255, 255, 0.05); color: #fff; }
        .sidebar-link.active { background-color: rgba(255, 255, 255, 0.1); color: #fff; border-left: 4px solid #81b64c; padding-left: 11px; }
        .btn-logout { background-color: rgba(62, 37, 16, 0.5); color: #e74c3c; margin-top: auto; }
        .sidebar-link.btn-logout:hover { background-color: #c0392b; color: #fff; }

        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            width: calc(100% - 240px);
            height: 100vh;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
        }

        .dashboard-inner {
            width: 100%;
            max-width: 1300px;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .panel {
            background-color: rgba(33, 21, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            box-sizing: border-box;
            min-height: 0;
        }

        .search-area {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
        }

        .search-area input {
            flex: 1;
            padding: 14px;
            border: 1px solid #5d3a1a;
            border-radius: 6px;
            background-color: rgba(0, 0, 0, 0.4);
            color: #fff;
            font-size: 15px;
            outline: none;
        }
        .search-area input:focus { border-color: #81b64c; }

        .clans-container {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
            padding-right: 5px;
        }

        .clan-card-item {
            background-color: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(93, 58, 26, 0.4);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .clan-info-left {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .clan-name-title {
            font-size: 18px;
            font-weight: bold;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .clan-meta-stats {
            font-size: 13.5px;
            color: #a8947a;
        }

        .clan-card-bio {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #c4b49c;
        }

        .clan-actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            border: none;
            transition: filter 0.2s, transform 0.1s;
            cursor: pointer;
            box-shadow: 0 4px 0 rgba(0,0,0,0.2);
            white-space: nowrap;
        }
        .btn:active { transform: translateY(2px); box-shadow: 0 2px 0 rgba(0,0,0,0.2); }
        .btn-play { background-color: #81b64c; border-bottom: 4px solid #68943b; text-transform: uppercase; }
        .btn-play:hover { background-color: #95cc5a; }
        .btn-chat { background-color: #7a4a28; border-bottom: 4px solid #4a2e14; }
        .btn-chat:hover { background-color: #8c5630; }

        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
        .alert-success { background-color: rgba(129, 182, 76, 0.2); color: #81b64c; border: 1px solid rgba(129, 182, 76, 0.4); }
        .alert-danger { background-color: rgba(192, 57, 43, 0.2); color: #e74c3c; border: 1px solid rgba(192, 57, 43, 0.4); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">⚪ Jeu de Dames</div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link">🏠 Accueil</a>
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link active">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        <div class="dashboard-inner">
            <h1 style="color: #fff; margin-top: 0; margin-bottom: 25px; font-weight: 600;">Rechercher un Clan</h1>
            
            <div class="panel">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="GET" action="" class="search-area">
                    <input type="text" name="search" placeholder="Entrez le nom d'un clan..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" autocomplete="off">
                    <button type="submit" class="btn btn-chat">Rechercher</button>
                </form>

                <div class="clans-container">
                    <?php if (empty($clansListe)): ?>
                        <p style="text-align: center; font-style: italic; color: #a8947a; margin-top: 40px;">Aucun clan ne correspond à votre recherche.</p>
                    <?php else: ?>
                        <?php foreach ($clansListe as $clan): ?>
                            <div class="clan-card-item">
                                <div class="clan-info-left">
                                    <div class="clan-name-title">
                                        <span><?php echo htmlspecialchars($clan['nom']); ?></span>
                                        <span style="font-size: 20px;"><?php echo htmlspecialchars($clan['drapeau']); ?></span>
                                    </div>
                                    <div class="clan-meta-stats">
                                        🏆 <strong><?php echo $clan['trophees']; ?></strong> trophées &bull; 👥 <strong><?php echo $clan['membres']; ?>/30</strong> membres &bull; 🌐 <i><?php echo htmlspecialchars($clan['langue']); ?></i>
                                    </div>
                                    <p class="clan-card-bio"><?php echo htmlspecialchars($clan['bio']); ?></p>
                                </div>
                                
                                <div class="clan-actions-right">
                                    <a href="clan_membres.php?id=<?php echo $clan['id']; ?>" class="btn btn-chat">👁️ Voir</a>
                                    
                                    <form method="POST" action="" style="margin: 0;">
                                        <input type="hidden" name="action" value="rejoindre">
                                        <input type="hidden" name="clan_id" value="<?php echo $clan['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" class="btn btn-play">Rejoindre</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>