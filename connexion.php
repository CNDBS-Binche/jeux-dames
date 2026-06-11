<?php
session_start();
require_once 'config.php';

$erreur = '';

// 1. TRAITEMENT DE LA CONNEXION (Si le formulaire est soumis)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SESSION['user_id'])) {
    $identifiant = trim($_POST['identifiant'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($identifiant === '' || $password === '') {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $req = $bdd->prepare('SELECT id, pseudo, mot_de_passe FROM utilisateurs WHERE email = ? OR pseudo = ? LIMIT 1');
        $req->execute([$identifiant, $identifiant]);
        $user_data = $req->fetch();

        if ($user_data && password_verify($password, $user_data['mot_de_passe'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['pseudo']  = $user_data['pseudo'];
            
            // Redirection sur soi-même pour rafraîchir la page en mode "connecté"
            header('Location: index.php');
            exit();
        } else {
            $erreur = 'Identifiant ou mot de passe incorrect.';
        }
    }
}

// 2. CHARGEMENT DES DONNÉES UTILISATEUR (Connecté vs Invité)
if (isset($_SESSION['user_id'])) {
    $query = $bdd->prepare('SELECT pseudo, date_inscription, avatar FROM utilisateurs WHERE id = ?');
    $query->execute([$_SESSION['user_id']]);
    $user = $query->fetch();
    
    // Récupération des vraies statistiques depuis la base si disponible
    $victoires = $user['victoires'] ?? 0;
    $defaites = $user['defaites'] ?? 0;
} else {
    // Profil générique pour les visiteurs non connectés
    $user = [
        'pseudo' => 'Invité',
        'date_inscription' => date('Y-m-d'),
        'avatar' => ''
    ];
    $victoires = 0;
    $defaites = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeux de Dames - Menu Principal</title>
    <style>
        /* ==========================================================================
           1. LE FOND D'ÉCRAN BRUN BOISÉ ET SON QUADRILLAGE ANIMÉ (FIXE)
           ========================================================================== */
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5; 
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
            overflow: hidden;
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

        /* ZONE D'AUTHENTIFICATION AGRANDIE */
        .sidebar-auth-zone {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 0 5px;
        }

        .btn-sidebar-auth {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            padding: 14px 15px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 15px;
            transition: background-color 0.2s, color 0.2s, transform 0.1s;
            box-sizing: border-box;
            width: 100%;
        }

        .btn-sidebar-auth:active {
            transform: scale(0.98);
        }

        .btn-login {
            background-color: rgba(129, 182, 76, 0.2);
            color: #81b64c;
            border: 1px solid rgba(129, 182, 76, 0.4);
        }
        .btn-login:hover {
            background-color: #81b64c;
            color: #fff;
        }

        .btn-register {
            background-color: rgba(122, 74, 40, 0.5);
            color: #f0d9b5;
            border: 1px solid rgba(122, 74, 40, 0.7);
        }
        .btn-register:hover {
            background-color: #7a4a28;
            color: #fff;
        }

        .btn-logout {
            background-color: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.4);
        }
        .btn-logout:hover {
            background-color: #e74c3c;
            color: #fff;
        }

        /* ==========================================================================
           3. CONTENU PRINCIPAL
           ========================================================================== */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            height: 100vh;
            overflow-y: auto;
            box-sizing: border-box;
            z-index: 1;
            display: flex;
            flex-direction: column;
        }

        .main-container {
            max-width: 1200px;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
        }

        .user-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(43, 29, 18, 0.6);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .user-profile-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-profile-link {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
            color: inherit;
            transition: opacity 0.2s;
        }
        
        .user-profile-link:hover {
            opacity: 0.85;
        }

        .user-avatar img, .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(255, 255, 255, 0.1);
            font-size: 24px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 30px;
            align-items: start;
        }

        .left-column, .right-column {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .card {
            background-color: rgba(43, 29, 18, 0.6);
            border-radius: 10px;
            padding: 25px;
            box-sizing: border-box;
        }

        .card h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 20px;
            color: #fff;
        }

        .card p {
            margin-top: 0;
            margin-bottom: 20px;
            color: #c4b49c;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn {
            display: block;
            text-align: center;
            padding: 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 15px;
            transition: opacity 0.2s;
            border: none;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-primary {
            background-color: #5c3d24;
            color: #fff;
        }

        .stat-box {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            background-color: rgba(0, 0, 0, 0.15);
            padding: 15px;
            border-radius: 6px;
            justify-content: space-around;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-val {
            font-size: 24px;
            font-weight: bold;
        }

        .stat-lbl {
            font-size: 12px;
            color: #a8947a;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .empty-state {
            font-style: italic;
            text-align: center;
            opacity: 0.6;
        }

        .boards-container {
            display: grid;
            grid-template-columns: 1fr;
            margin-top: 20px;
            max-width: 250px;
            margin-left: auto;
            margin-right: auto;
        }

        .board-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s;
        }

        .board-wrapper:hover {
            transform: translateY(-5px);
        }

        .mini-board {
            width: 100%;
            aspect-ratio: 1 / 1;
            display: flex;
            flex-direction: column;
            border: 4px solid #312115;
            border-radius: 4px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
            box-sizing: border-box;
        }

        .board-row {
            display: flex;
            flex: 1;
            width: 100%;
        }

        .cell {
            flex: 1;
            aspect-ratio: 1 / 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .board-row:nth-child(odd) .cell:nth-child(even),
        .board-row:nth-child(even) .cell:nth-child(odd) {
            background-color: #b58863;
        }

        .board-row:nth-child(odd) .cell:nth-child(odd),
        .board-row:nth-child(even) .cell:nth-child(even) {
            background-color: #f0d9b5;
        }

        .piece {
            width: 75%;
            height: 75%;
            border-radius: 50%;
            box-shadow: inset 0 2px 4px rgba(255,255,255,0.3), 0 2px 4px rgba(0,0,0,0.5);
        }

        .piece.white { background-color: #eaeaea; border: 1px solid #bcbcbc; }
        .piece.black { background-color: #2b2b2b; border: 1px solid #111; }

        .board-label {
            margin-top: 12px;
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .history-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.2);
            padding: 12px 15px;
            border-radius: 6px;
            border-left: 4px solid #fff;
        }

        .history-item.win { border-left-color: #81b64c; }
        .history-item.lose { border-left-color: #e74c3c; }

        .history-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .history-opponent { font-weight: bold; color: #fff; font-size: 14px; }
        .history-date { font-size: 11px; color: #a8947a; }

        .history-badge {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            color: #fff;
            text-align: center;
            min-width: 65px;
            display: inline-block;
        }
        .win .history-badge { background-color: rgba(129, 182, 76, 0.2); color: #81b64c; }
        .lose .history-badge { background-color: rgba(231, 76, 60, 0.2); color: #e74c3c; }

        /* ==========================================================================
           4. RECOUVREMENT DE CONNEXION OPTIMISÉ POUR CORRESPONDRE À LA PHOTO
           ========================================================================== */
        .overlay {
            position: fixed;
            top: 0;
            left: 240px; /* Aligné après la barre latérale comme demandé */
            width: calc(100% - 240px);
            height: 100%;
            background-color: transparent; /* Pas de fond noir opaque, préserve l'arrière-plan boisé */
            z-index: 100;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: rgba(43, 29, 18, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 40px;
            border-radius: 12px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            box-sizing: border-box;
            color: #f0d9b5;
        }

        .login-container h1 {
            margin-top: 0;
            margin-bottom: 5px;
            font-size: 28px;
            color: #fff;
            text-align: center;
        }

        .subtitle {
            margin-top: 0;
            margin-bottom: 30px;
            color: #a8947a;
            text-align: center;
            font-size: 14px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #c4b49c;
            font-weight: 600;
            font-size: 14px;
        }

        .input-group input {
            width: 100%;
            padding: 12px;
            background-color: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(122, 74, 40, 0.6);
            border-radius: 6px;
            color: #fff;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }

        .input-group input:focus {
            border-color: #81b64c;
        }

        .alert.error {
            background-color: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
        }

        .switch-mode {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #c4b49c;
        }

        .switch-mode a {
            color: #81b64c;
            text-decoration: none;
            font-weight: 600;
        }

        .switch-mode a:hover {
            text-decoration: underline;
        }
        
        .btn-submit-login {
            background-color: #81b64c;
            color: #fff;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
             ⚪ Jeu de Dames
        </div>
        <div class="sidebar-menu">
            <a href="index.php" class="sidebar-link active">🏠 Accueil</a>
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link">🛡️ Clans</a>
        </div>
        
        <div class="sidebar-auth-zone">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="deconnexion.php" class="btn-sidebar-auth btn-logout">🚪 Déconnexion</a>
            <?php else: ?>
                <a href="index.php" class="btn-sidebar-auth btn-login">👤 Connexion</a>
                <a href="inscription.php" class="btn-sidebar-auth btn-register">📝 Inscription</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="main-container">
                <div class="user-header">
                    <div class="user-profile-link">
                        <div class="user-profile-info">
                            <div class="user-avatar">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Photo de profil">
                                <?php else: ?>
                                    👤
                                <?php endif; ?>
                            </div>
                            <div>
                                <h1 style="margin:0; font-size: 24px; color:#fff;"><?php echo htmlspecialchars($user['pseudo']); ?></h1>
                            </div>
                        </div>
                    </div>
                    <div style="font-size: 13px; color: #a8947a;">
                        Visiteur ou membre depuis le <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="left-column">
                        <div class="card card-play">
                            <h2>Prêt à en découdre ?</h2>
                            <p style="color: #f0d9b5; opacity: 0.8;">Défiez des joueurs en ligne, peaufinez vos tactiques et grimpez dans le classement du jeu de dames.</p>
                            
                            <div class="boards-container">
                                <a href="plateau.php" class="board-wrapper">
                                    <div class="mini-board">
                                        <div class="board-row">
                                            <div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div>
                                        </div>
                                        <div class="board-row">
                                            <div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div>
                                        </div>
                                        <div class="board-row">
                                            <div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div><div class="cell"></div><div class="cell"><div class="piece black"></div></div>
                                        </div>
                                        <div class="board-row"><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div></div>
                                        <div class="board-row"><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div></div>
                                        <div class="board-row"><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div></div>
                                        <div class="board-row"><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div><div class="cell"></div></div>
                                        <div class="board-row">
                                            <div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div>
                                        </div>
                                        <div class="board-row">
                                            <div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div>
                                        </div>
                                        <div class="board-row">
                                            <div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div><div class="cell"><div class="piece white"></div></div><div class="cell"></div>
                                        </div>
                                    </div>
                                    <div class="board-label">Lancer une partie</div>
                                </a>
                            </div>
                        </div>

                        <div class="card">
                            <h2>📜 Historique des dernières parties</h2>
                            <div class="history-list">
                                <div class="history-item win">
                                    <div class="history-details">
                                        <span class="history-opponent">Contre Maxlamenace2207</span>
                                        <span class="history-date">Le 09/06/2026 à 11:20</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <span class="history-badge">Victoire</span>
                                    </div>
                                </div>

                                <div class="history-item lose">
                                    <div class="history-details">
                                        <span class="history-opponent">Contre Ordinateur (Facile)</span>
                                        <span class="history-date">Le 08/06/2026 à 18:45</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 15px;">
                                        <span class="history-badge">Défaite</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="right-column">
                        <div class="card">
                            <h2>📊 Mes Statistiques</h2>
                            <div class="stat-box">
                                <div class="stat-item">
                                    <span class="stat-val" style="color: #81b64c;"><?php echo $victoires; ?></span>
                                    <span class="stat-lbl">Victoires</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-val" style="color: #e74c3c;"><?php echo $defaites; ?></span>
                                    <span class="stat-lbl">Défaites</span>
                                </div>
                            </div>
                            <a href="profil.php" class="btn btn-primary">Détail du profil</a>
                        </div>

                        <div class="card">
                            <h2>👥 Liste d'amis</h2>
                            <p class="empty-state">Aucun ami en ligne pour le moment.</p>
                            <a href="amis.php" class="btn btn-primary">Gérer mes amis</a>
                        </div>

                        <div class="card">
                            <h2>🛡️ Mon Clan</h2>
                            <p>Créez une alliance, arborez un tag unique et participez au classement général des clans.</p>
                            <a href="clans.php" class="btn btn-primary">Accéder aux Clans</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="overlay">
            <div class="login-container">
                <h1>Connexion</h1>
                <p class="subtitle">Heureux de vous revoir !</p>

                <?php if (!empty($erreur)): ?>
                    <div class="alert error"><?php echo htmlspecialchars($erreur); ?></div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <div class="input-group">
                        <label for="identifiant">Email ou nom d'utilisateur</label>
                        <input type="text" id="identifiant" name="identifiant" required
                               placeholder="Votre pseudo ou email"
                               value="<?php echo htmlspecialchars($_POST['identifiant'] ?? ''); ?>">
                    </div>
                    <div class="input-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn btn-submit-login">Se connecter</button>
                </form>

                <div class="switch-mode">
                    Pas encore de compte ? <a href="inscription.php">S'inscrire</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (file_exists('popup_invitation.php')) { include 'popup_invitation.php'; } ?>
</body>
</html>