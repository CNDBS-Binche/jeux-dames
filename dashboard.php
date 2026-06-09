<?php
session_start();
require_once 'config.php';

// 1. SÉCURITÉ : Si l'utilisateur n'est pas connecté, redirection
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

// 2. RÉCUPÉRATION DES INFOS EN DIRECT DE LA BDD
$query = $bdd->prepare('SELECT pseudo, date_inscription, avatar FROM utilisateurs WHERE id = ?');
$query->execute([$_SESSION['user_id']]);
$user = $query->fetch();

// Si les statistiques ne sont pas encore calculées, on initialise à 0
$victoires = isset($victoires) ? $victoires : 0;
$defaites = isset($defaites) ? $defaites : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tableau de Bord - Jeu de Dames</title>
    <style>
        /* ==========================================================================
           1. LE FOND D'ÉCRAN BRUN BOISÉ ET SON QUADRILLAGE ANIMÉ (FIXE)
           ========================================================================== */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5; 
            background: radial-gradient(circle, #4a321f, #2b1d12);
            position: relative;
            /* Le navigateur gérera automatiquement l'unique barre à droite */
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

        .btn-logout {
            background-color: rgba(62, 37, 16, 0.5);
            color: #e74c3c;
            margin-top: auto;
        }
        .sidebar-link.btn-logout:hover { 
            background-color: #c0392b; color: #fff; 
        }

        /* ==========================================================================
           3. CONTENU PRINCIPAL CADRÉ 
           ========================================================================== */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            max-width: 1200px;
            width: calc(100% - 240px);
            /* Suppression de height: 100vh et overflow-y: auto pour éviter les doublons */
            box-sizing: border-box;
            z-index: 1;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">
            ⚪ Jeu de Dames
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="sidebar-link active">🏠 Accueil</a>
            <a href="plateau.php" class="sidebar-link">⚔️ Jouer</a>
            <a href="hub.php" class="sidebar-link">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        
        <div class="user-header">
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
            <div style="font-size: 13px; color: #a8947a;">
                Inscrit le <?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?>
            </div>
        </div>

        <div class="dashboard-grid">
            
            <div class="left-column">
                <div class="card card-play">
                    <h2>Prêt à en découdre ?</h2>
                    <p style="color: #f0d9b5; opacity: 0.8;">Défiez des joueurs en ligne, peaufinez vos tactiques et grimpez dans le classement du jeu de dames.</p>
                    <a href="plateau.php" class="btn btn-play">Lancer une partie</a>
                </div>

                <div class="card">
                    <h2>💬 Salon Public & Chat</h2>
                    <p>Rejoignez le Hub pour discuter avec les joueurs connectés en temps réel et voir l'activité de la communauté globale.</p>
                    <a href="hub.php" class="btn btn-primary">Accéder au Hub</a>
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
</body>
</html>