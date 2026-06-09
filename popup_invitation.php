<div id="global-popup-invitation" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999; justify-content: center; align-items: center; font-family: sans-serif;">
    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; width: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.3);">
        <h3 style="margin-top: 0; color: #333;">⚔️ Nouveau défi reçu !</h3>
        <p id="popup-texte-defi" style="color: #666; margin: 15px 0;">Un joueur vous défie aux dames.</p>
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
            <button id="btn-popup-accepter" style="background: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Accepter</button>
            <button id="btn-popup-refuser" style="background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Refuser</button>
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

    function verifierDefisGlobaux() {
        if (window.location.href.includes('plateau.php')) return;

        // Appel direct vers ton action avec un chemin relatif blindé (./)
        fetch('./jcj_ajax.php?action=verifier_defis')
            .then(res => res.json())
            .then(data => {
                if (data.type === 'recu') {
                    // Quelqu'un nous défie !
                    matchIdActuel = data.match_id;
                    textePopup.innerText = `Le joueur ${data.adversaire} vous défie !`;
                    popup.style.display = 'flex';
                } else if (data.type === 'lance_accepte') {
                    // Notre défi envoyé a été accepté, on est téléporté sur le plateau !
                    window.location.href = `plateau.php?match_id=${data.match_id}`;
                } else {
                    // Statut "aucun" (timeout des 15s), on relance directement la boucle
                    verifierDefisGlobaux();
                }
            })
            .catch(() => {
                setTimeout(verifierDefisGlobaux, 3000);
            });
    }

    if (btnAccepter) {
        btnAccepter.addEventListener('click', function() {
            if (!matchIdActuel) return;
            const params = new URLSearchParams();
            params.append('match_id', matchIdActuel);
            params.append('decision', 'accepte');

            fetch('./jcj_ajax.php?action=repondre', { method: 'POST', body: params })
                .then(res => res.json())
                .then(data => {
                    if (data.statut === 'succes') {
                        window.location.href = `plateau.php?match_id=${matchIdActuel}`;
                    }
                });
        });
    }

    if (btnRefuser) {
        btnRefuser.addEventListener('click', function() {
            if (!matchIdActuel) return;
            const params = new URLSearchParams();
            params.append('match_id', matchIdActuel);
            params.append('decision', 'refuse');

            fetch('./jcj_ajax.php?action=repondre', { method: 'POST', body: params })
                .then(() => {
                    popup.style.display = 'none';
                    setTimeout(verifierDefisGlobaux, 1000);
                });
        });
    }

    verifierDefisGlobaux();
});
</script>