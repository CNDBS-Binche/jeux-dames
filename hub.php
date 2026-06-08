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
        body {
            background-color: #2c3e50;
            color: #f0d9b5;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .hub-container {
            display: flex;
            gap: 20px;
            width: 80%;
            max-width: 1000px;
            height: 70vh;
        }
        /* Style boisé pour les sections du Hub */
        .panel {
            background-color: #5d3a1a;
            border: 5px solid #4a2e14;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            display: flex;
            flex-direction: column;
        }
        .menu-panel { flex: 1; }
        .chat-panel { flex: 2; }
        
        h2 { border-bottom: 2px solid #7a4a28; padding-bottom: 5px; color: #fff; margin-top: 0; }
        
        /* Fenêtre de chat */
        #chat-box {
            flex: 1;
            background-color: #3e2510;
            border: 2px solid #4a2e14;
            border-radius: 4px;
            padding: 10px;
            overflow-y: auto;
            margin-bottom: 10px;
            font-size: 14px;
        }
        #chat-box p { margin: 5px 0; line-height: 1.4; }
        
        /* Formulaire d'envoi */
        .chat-form { display: flex; gap: 5px; }
        .chat-form input[type="text"] {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 4px;
            background-color: #f0d9b5;
            color: #333;
        }
        .chat-form button {
            background-color: #7a4a28;
            color: #fff;
            border: 1px solid #4a2e14;
            padding: 8px 15px;
            cursor: pointer;
            border-radius: 4px;
            font-weight: bold;
        }
        .chat-form button:hover { background-color: #8c5630; }
        
        .btn-jeu {
            display: block;
            text-align: center;
            background-color: #27ae60;
            color: white;
            padding: 15px;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            margin-top: 20px;
            box-shadow: 0 4px 0 #1e7e43;
        }
        .btn-jeu:hover { background-color: #2ecc71; }
    </style>
</head>
<body>

<h1>Bienvenue sur le Hub, <?php echo htmlspecialchars($username); ?> !</h1>

<div class="hub-container">
    <div class="panel menu-panel">
        <h2>Menu principal</h2>
        <p>Clan : Aucun (Bientôt disponible)</p>
        
        <a href="plateau.php" class="btn-jeu">Jouer (Local)</a>
    </div>

    <div class="panel chat-panel">
        <h2>Chat Global</h2>
        <div id="chat-box"></div>
        
        <form class="chat-form" id="form-chat">
            <input type="text" id="msg-input" placeholder="Écrivez un message..." maxlength="255" required autocomplete="off">
            <button type="submit">Envoyer</button>
        </form>
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
    .catch(() => {}); // silencieux si le serveur est indisponible
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
        msgInput.value = ''; // On vide le champ de saisie
        chargerChat();       // On rafraîchit immédiatement le chat
    });
});

// 3. Recharger le chat toutes les 2 secondes (le fameux Polling d'OVH)
setInterval(chargerChat, 2000);

// Chargement initial au démarrage de la page
chargerChat();
</script>
<div id="pop-up-defi" style="display:none; position:fixed; bottom:20px; right:20px; background-color:#d35400; border:4px solid #a04000; padding:20px; border-radius:8px; box-shadow:0 10px 20px rgba(0,0,0,0.5); z-index:9999; color:white;">
    <p id="texte-defi" style="margin:0 0 15px 0; font-weight:bold;"></p>
    <div style="display:flex; gap:10px;">
        <button id="btn-accepter-defi" class="btn" style="background-color:#27ae60; margin:0;">Accepter</button>
        <button id="btn-refuser-defi" class="btn" style="background-color:#c0392b; margin:0;">Refuser</button>
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
            // Redirection immédiate vers le plateau de jeu en transmettant l'ID de la partie !
            window.location.href = './plateau.php?match_id=' + ID_MATCH_ACTUEL;
        }
    });
}

// On vérifie toutes les 3 secondes si quelqu'un nous défie
setInterval(ecouterDefis, 3000);
</script>
</body>
</html>