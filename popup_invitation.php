<?php
// Sécurité : Si l'utilisateur n'est pas connecté, on n'affiche rien et on n'exécute pas le script
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    return;
}
?>

<div id="global-popup-invitation" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center; font-family: sans-serif;">
    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; width: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
        <h3 style="margin-top: 0; color: #333; font-size: 22px;">⚔️ Nouveau défi reçu !</h3>
        <p id="popup-texte-defi" style="color: #666; margin: 15px 0; font-size: 16px;">Un joueur vous défie aux dames.</p>
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
            <button id="btn-popup-accepter" style="background: #2ecc71; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 15px;">Accepter</button>
            <button id="btn-popup-refuser" style="background: #e74c3c; color: white; border: none; padding: 12px 25px; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 15px;">Refuser</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById('global-popup-invitation');
    const textePopup = document.getElementById('popup-texte-defi');
    const btnAccepter = document.getElementById('btn-popup-accepter');
    const btnRefuser = document.getElementById('btn-popup-refuser');
    
    let matchIdActuel = null;

    function verifierInvitationsEntrantes() {
        // Sécurité absolue : Si on est DÉJÀ en train de jouer sur plateau.php, 
        // on coupe l'écoute pour ne pas être interrompu en pleine partie.
        if (window.location.href.includes('plateau.php')) return;

        fetch('jcj_ajax.php?action=ecouter_invitations_recues')
            .then(response => {
                if (!response.ok) throw new Error("Erreur serveur");
                return response.json();
            })
            .then(data => {
                if (data.statut === 'nouveau_defi') {
                    matchIdActuel = data.match_id;
                    textePopup.innerText = `Le joueur ${data.pseudo_adversaire} vous défie pour une partie !`;
                    popup.style.display = 'flex'; // La popup surgit à l'écran
                } else {
                    // Si Timeout (15s sans invitation), on relance l'écoute immédiatement
                    verifierInvitationsEntrantes();
                }
            })
            .catch(err => {
                console.log("Serveur indisponible ou déconnecté, nouvelle tentative dans 3s...");
                setTimeout(verifierInvitationsEntrantes, 3000);
            });
    }

    // Clic sur "Accepter"
    if (btnAccepter) {
        btnAccepter.addEventListener('click', function() {
            if (!matchIdActuel) return;
            
            const formData = new FormData();
            formData.append('match_id', matchIdActuel);

            fetch('jcj_ajax.php?action=accepter_defi', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.statut === 'succes') {
                        // Redirection instantanée sur le plateau de jeu !
                        window.location.href = `plateau.php?match_id=${matchIdActuel}`;
                    }
                });
        });
    }

    // Clic sur "Refuser"
    if (btnRefuser) {
        btnRefuser.addEventListener('click', function() {
            popup.style.display = 'none';
            
            // Optionnel : Tu peux envoyer une requête à jcj_ajax.php pour supprimer/refuser le défi en BDD ici
            
            // On relance la recherche d'invitations après 2 secondes de répit
            setTimeout(verifierInvitationsEntrantes, 2000);
        });
    }

    // Lancement automatique de l'écoute en arrière-plan
    verifierInvitationsEntrantes();
});
</script>