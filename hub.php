<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
// Récupère la session 'pseudo' ou utilise 'Joueur' par défaut si elle n'est pas définie
$username = $_SESSION['pseudo'] ?? 'Joueur'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Hub - Jeu de Dames</title>
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

        .btn-logout {
            background-color: rgba(62, 37, 16, 0.5);
            color: #e74c3c;
            margin-top: auto;
        }
        .sidebar-link.btn-logout:hover { 
            background-color: #c0392b; color: #fff; 
        }

        /* ==========================================================================
           3. CONTENU PRINCIPAL & GRILLE
           ========================================================================== */
        .main-content {
            margin-left: 240px;
            flex-grow: 1;
            padding: 40px;
            max-width: 1200px;
            width: calc(100% - 240px);
            z-index: 1;
        }

        .hub-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            height: 65vh;
        }

        @media (max-width: 950px) {
            .hub-grid {
                grid-template-columns: 1fr;
                height: auto;
            }
        }

        .panel {
            background-color: rgba(33, 21, 13, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
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

        .panel p {
            color: #c4b49c;
            font-size: 14px;
            line-height: 1.6;
        }
        
        /* ==========================================================================
           4. CHAT ET BOUTONS
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
            margin: 5px 0; 
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

        .btn-play {
            background-color: #81b64c;
            border-bottom: 4px solid #68943b;
            text-transform: uppercase;
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            margin-top: auto;
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
            <a href="hub.php" class="sidebar-link active">💬 Salon Public</a>
            <a href="profil.php" class="sidebar-link">⚙️ Profil</a>
            <a href="amis.php" class="sidebar-link">👥 Amis</a>
            <a href="clans.php" class="sidebar-link">🛡️ Clans</a>
        </div>
        <a href="deconnexion.php" class="sidebar-link btn-logout">🚪 Déconnexion</a>
    </div>

    <div class="main-content">
        <h1 style="color: #fff; margin-top: 0; margin-bottom: 30px; font-weight: 600;">Bienvenue sur le chat public, <?php echo htmlspecialchars($username); ?> !</h1>

        <div class="hub-grid">
            <div class="panel">
                <h2>Menu principal</h2>
                <p>Clan : Aucun (Bientôt disponible)</p>
                
                <a href="plateau.php" class="btn btn-play">Jouer</a>
            </div>

            <div class="panel">
                <h2>Chat Global</h2>
                <div id="chat-box"></div>
                
                <form class="chat-form" id="form-chat">
                    <input type="text" id="msg-input" placeholder="Écrivez un message..." maxlength="255" required autocomplete="off">
                    <button type="submit" class="btn btn-chat">Envoyer</button>
                </form>
            </div>
        </div>
    </div>

<script>
const chatBox = document.getElementById('chat-box');
const formChat = document.getElementById('form-chat');
const msgInput = document.getElementById('msg-input');

// 1. Fonction AJAX pour récupérer les messages
function chargerChat() {
    fetch('chat_ajax.php?action=recuperer')
    .then(r => r.json())
    .then(messages => {
        const isScrolledToBottom = chatBox.scrollHeight - chatBox.clientHeight <= chatBox.scrollTop + 50;
        chatBox.innerHTML = messages.length === 0
            ? "<p style='color:#a1887f;text-align:center;font-style:italic;padding-top:20px;'>Le chat est vide.</p>"
            : messages.map(m =>
                `<p style='margin:4px 0'><strong>[${m.heure}] ${escHtml(m.pseudo)} :</strong> ${escHtml(m.message)}</p>`
              ).join('');
        if (isScrolledToBottom) chatBox.scrollTop = chatBox.scrollHeight;
    })
    .catch(() => {});
}
function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// 2. Fonction AJAX pour envoyer un message sans recharger la page
formChat.addEventListener('submit', function(e) {
    e.preventDefault();
    const message = msgInput.value;
    if(!message.trim()) return;

    const formData = new FormData();
    formData.append('message', message);

    fetch('chat_ajax.php?action=envoyer', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        msgInput.value = ''; 
        chargerChat();       
    });
});

setInterval(chargerChat, 2000);
chargerChat();
</script>

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

document.getElementById('btn-accepter-defi').addEventListener('click', () => {
    repondreDefi('accepte');
});

document.getElementById('btn-refuser-defi').addEventListener('click', () => {
    repondreDefi('refuse');
});

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
</body>
</html>