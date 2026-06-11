<?php
session_start();
require_once 'config.php';

// 1. VÉRIFICATION DE LA SESSION
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['pseudo'] ?? 'Joueur';

// 2. SIMULATION DE L'ÉTAT DU JOUEUR (À basculer sur 'true' pour tester l'affichage complet du clan)
$aUnClan = false; 

// Données fictives si le joueur a un clan (À remplacer par des requêtes SQL)
$clanData = [
    'clan_id' => 1,
    'nom' => 'Les Maîtres du Damier',
    'avatar' => 'images/clans/default.png',
    'bio' => 'Bienvenue chez les cracks. Ici, on ne laisse passer aucun pion. Objectif : le top du classement mondial ! ⚔️',
    'chef' => 'DraughtsMaster99',
    'trophees' => 4520,
    'drapeau' => '🇫🇷'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Clan - Jeu de Dames</title>
    <style>
        /* ==========================================================================
           1. FOND D'ÉCRAN FIXE ET QUADRILLAGE ANIMÉ
           ========================================================================== */
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #f0d9b5;
            overflow: hidden; /* Empêche le scroll global pour garder l'interface fixe */
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
           2. BARRE LATÉRALE DE NAVIGATION
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
           3. CONTENU PRINCIPAL ET GRILLE DE TAILLE 1300PX
           ========================================================================== */
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

        .clan-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            flex-grow: 1;
            min-height: 0; /* Important : Débloque l'overflow interne */
            margin-bottom: 20px;
        }

        /* ==========================================================================
           4. PANELS ET EN-TÊTE DU CLAN
           ========================================================================== */
        .panel {
            background-color: rgba(33, 21, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            box-sizing: border-box;
            min-height: 0;
        }

        .panel h2 {
            margin-top: 0;
            color: #fff;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
            border-bottom: 2px solid #7a4a28;
            padding-bottom: 10px;
        }

        /* En-tête Global du Clan (Quand on en a un) */
        .clan-header-panel {
            flex-direction: row;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
            padding: 25px 30px;
        }

        .clan-avatar {
            width: 100px;
            height: 100px;
            border-radius: 10px;
            border: 3px solid #5d3a1a;
            background-color: #1b120b;
            object-fit: cover;
        }

        .clan-infos-main {
            flex-grow: 1;
        }

        .clan-title-area {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 6px;
        }

        .clan-title-area h1 {
            margin: 0;
            font-size: 30px;
            color: #fff;
            font-weight: bold;
        }

        .clan-flag {
            font-size: 26px;
        }

        .clan-bio {
            margin: 0;
            font-size: 14.5px;
            color: #c4b49c;
            line-height: 1.5;
        }

        .clan-stats-header {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 240px;
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #5d3a1a;
            font-size: 14px;
        }

        .clan-stats-header strong {
            color: #fff;
        }

        /* ==========================================================================
           5. SYSTEME DE CHAT PRIVE DE CLAN (Inspiré du Hub)
           ========================================================================== */
        #chat-box {
            flex: 1;
            background-color: rgba(0, 0, 0, 0.4);
            border: 1px solid #5d3a1a;
            border-radius: 6px;
            padding: 15px;
            overflow-y: auto;
            margin-bottom: 15px;
            font-size: 14px;
        }

        #chat-box p { 
            margin: 6px 0; 
            line-height: 1.4; 
            color: #f0d9b5;
        }

        .chat-form { 
            display: flex; 
            gap: 10px; 
        }

        .chat-form input[type="text"] {
            flex: 1;
            padding: 12px;
            border: 1px solid #5d3a1a;
            border-radius: 6px;
            background-color: rgba(0, 0, 0, 0.4);
            color: #fff;
            font-size: 14px;
            outline: none;
        }

        .chat-form input[type="text"]:focus {
            border-color: #81b64c;
        }

        /* ==========================================================================
           6. CONTENEURS DE BOUTONS ET REDIRECTIONS
           ========================================================================== */
        .clan-menu-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
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
            font-size: 15px;
            border: none;
            transition: filter 0.2s, transform 0.1s;
            cursor: pointer;
            box-shadow: 0 4px 0 rgba(0,0,0,0.2);
            box-sizing: border-box;
        }

        .btn:active {
            transform: translateY(2px);
            box-shadow: 0 2px 0 rgba(0,0,0,0.2);
        }

        .btn-play {
            background-color: #81b64c;
            border-bottom: 4px solid #68943b;
            text-transform: uppercase;
            width: 100%;
            text-align: center;
        }
        .btn-play:hover { 
            background-color: #95cc5a; 
        }

        .btn-chat {
            background-color: #7a4a28;
            border-bottom: 4px solid #4a2e14;
        }
        .btn-chat:hover {
            background-color: #8c5630;
        }

        .btn-nav {
            background-color: rgba(122, 74, 40, 0.4);
            border: 1px solid #5d3a1a;
            border-bottom: 3px solid #4a2e14;
            justify-content: flex-start;
            padding-left: 15px;
        }
        .btn-nav:hover {
            background-color: rgba(122, 74, 40, 0.7);
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
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link active">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        <div class="dashboard-inner">

            <?php if (!$aUnClan): ?>
                
                <h1 style="color: #fff; margin-top: 0; margin-bottom: 30px; font-weight: 600;">Factions & Clans</h1>
                
                <div class="panel" style="text-align: center; align-items: center; padding: 60px 30px; justify-content: center; margin-top: auto; margin-bottom: auto;">
                    <span style="font-size: 60px; margin-bottom: 20px;">🛡️</span>
                    <h2>Pas encore de clan !</h2>
                    <p style="font-size: 15px; max-width: 520px; margin-bottom: 30px; color: #c4b49c;">
                        Pas encore de clans, rejoignez en un ou créez en un pour interagir avec les membres, suivre vos winstreaks et grimper au sommet.
                    </p>
                    <div style="display: flex; gap: 15px; width: 100%; max-width: 450px;">
                        <a href="recherche_clan.php" class="btn btn-play" style="flex: 1;">🔍 Rejoindre</a>
                        <a href="creer_clan.php" class="btn btn-chat" style="flex: 1; border-bottom-width: 4px;">✨ Créer un Clan</a>
                    </div>
                </div>

            <?php else: ?>

                <div class="panel clan-header-panel">
                    <img src="<?php echo htmlspecialchars($clanData['avatar']); ?>" alt="Logo Clan" class="clan-avatar">
                    
                    <div class="clan-infos-main">
                        <div class="clan-title-area">
                            <h1><?php echo htmlspecialchars($clanData['nom']); ?></h1>
                            <span class="clan-flag"><?php echo htmlspecialchars($clanData['drapeau']); ?></span>
                        </div>
                        <p class="clan-bio"><?php echo htmlspecialchars($clanData['bio']); ?></p>
                    </div>

                    <div class="clan-stats-header">
                        <div>👑 Chef : <strong><?php echo htmlspecialchars($clanData['chef']); ?></strong></div>
                        <div>🏆 Trophées globaux : <strong style="color: #f1c40f;"><?php echo number_format($clanData['trophees']); ?></strong></div>
                    </div>
                </div>

                <div class="clan-grid">
                    
                    <div class="panel">
                        <h2>💬 Chat Interne du Clan</h2>
                        <div id="chat-box"></div>
                        
                        <form class="chat-form" id="form-chat-clan">
                            <input type="text" id="msg-clan-input" placeholder="Envoyer un message à vos compagnons de faction..." maxlength="255" required autocomplete="off">
                            <button type="submit" class="btn btn-chat">Envoyer</button>
                        </form>
                    </div>

                    <div class="panel">
                        <h2>📌 Menu Faction</h2>
                        <div class="clan-menu-list">
                            <a href="clan_membres.php" class="btn btn-nav">👥 Liste des Membres</a>
                            <a href="clan_classement.php" class="btn btn-nav">📊 Classement des Clans</a>
                            <a href="clan_winstreak.php" class="btn btn-nav" style="border-left: 3px solid #81b64c;">🔥 Tops Winstreaks</a>
                        </div>
                        
                        <a href="quitter_clan.php" class="btn" style="background-color: rgba(192, 57, 43, 0.15); color: #e74c3c; font-size: 14px; padding: 10px; margin-top: auto;" onclick="return confirm('Êtes-vous sûr de vouloir abandonner votre clan ?');">🚪 Quitter le Clan</a>
                    </div>

                </div>

                <script>
                const chatBox = document.getElementById('chat-box');
                const formChatClan = document.getElementById('form-chat-clan');
                const msgClanInput = document.getElementById('msg-clan-input');

                // 1. Récupération AJAX des messages du clan
                function chargerChatClan() {
                    fetch('clan_chat_ajax.php?action=recuperer')
                    .then(r => r.json())
                    .then(messages => {
                        // Vérifie si l'utilisateur est descendu au fond
                        const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 50;
                        
                        chatBox.innerHTML = messages.length === 0
                            ? "<p style='color:#a1887f;text-align:center;font-style:italic;padding-top:20px;'>Aucun message dans le canal de la faction.</p>"
                            : messages.map(m =>
                                `<p style='margin:5px 0'><strong>[${m.heure}] ${escHtml(m.pseudo)} :</strong> ${escHtml(m.message)}</p>`
                              ).join('');
                              
                        if (isScrolledToBottom) chatBox.scrollTop = chatBox.scrollHeight;
                    })
                    .catch(() => {});
                }

                function escHtml(s) {
                    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                }

                // 2. Envoi asynchrone d'un message sans rechargement
                if (formChatClan) {
                    formChatClan.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const message = msgClanInput.value;
                        if(!message.trim()) return;

                        const formData = new FormData();
                        formData.append('message', message);

                        fetch('clan_chat_ajax.php?action=envoyer', {
                            method: 'POST',
                            body: formData
                        })
                        .then(() => {
                            msgClanInput.value = ''; 
                            chargerChatClan();       
                        });
                    });
                    
                    // Fréquence de rafraîchissement calée sur 2 secondes
                    setInterval(chargerChatClan, 2000);
                    chargerChatClan();
                }
                </script>

            <?php endif; ?>

        </div>
    </div>

    <div id="pop-up-defi" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#d35400; border:4px solid #a04000; padding:20px; border-radius:8px; box-shadow:0 10px 20px rgba(0,0,0,0.5); z-index:9999; color:white;">
        <p id="texte-defi" style="margin:0 0 15px 0; font-weight:bold;"></p>
        <div style="display:flex; gap:10px;">
            <button id="btn-accepter-defi" class="btn" style="background-color:#27ae60; margin:0; padding: 8px 15px;">Accepter</button>
            <button id="btn-refuser-defi" class="btn" style="background-color:#c0392b; margin:0; padding: 8px 15px;">Refuser</button>
        </div>
    </div>

    <script>
    let ID_MATCH_ACTUEL = null;

    function ecouterDefis() {
        fetch('./jcj_ajax.php?action=verifier_defis')
        .then(r => r.json())
        .then(data => {
            if(data.type === 'recu') {
                ID_MATCH_ACTUEL = data.match_id;
                document.getElementById('texte-defi').innerText = `⚔️ ${data.adversaire} vous défie en duel !`;
                document.getElementById('pop-up-defi').style.display = 'block';
            }
        });
    }

    document.getElementById('btn-accepter-defi').addEventListener('click', () => { repondreDefi('accepte'); });
    document.getElementById('btn-refuser-defi').addEventListener('click', () => { repondreDefi('refuse'); });

    function repondreDefi(decision) {
        const data = new FormData();
        data.append('match_id', ID_MATCH_ACTUEL);
        data.append('decision', decision);

        fetch('./jcj_ajax.php?action=repondre', { method: 'POST', body: data })
        .then(() => {
            document.getElementById('pop-up-defi').style.display = 'none';
            if(decision === 'accepte') {
                window.location.href = './plateau.php?match_id=' + ID_MATCH_ACTUEL;
            }
        });
    }

    setInterval(ecouterDefis, 3000);
    </script>
    <?php include 'popup_invitation.php'; ?>
</body>
</html>