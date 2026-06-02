<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Récupérer les demandes d'amis reçues et en attente
$reqDemandes = $bdd->prepare('
    SELECT a.id as relation_id, u.pseudo 
    FROM amis a 
    JOIN utilisateurs u ON a.user_id_1 = u.id 
    WHERE a.user_id_2 = ? AND a.statut = "en_attente"
');
$reqDemandes->execute([$userId]);
$demandes = $reqDemandes->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des amis acceptés
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
        body {
            background-color: #2c3e50;
            color: #f0d9b5;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        h1 { text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        
        .container {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .panel {
            background-color: #5d3a1a;
            border: 4px solid #4a2e14;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }

        h2 { margin-top: 0; border-bottom: 2px solid #7a4a28; padding-bottom: 8px; color: #fff; }

        /* Formulaire de recherche */
        .search-box { display: flex; gap: 10px; margin-top: 15px; }
        .search-box input {
            flex: 1; padding: 10px; border: none; border-radius: 4px; background-color: #f0d9b5; color: #333; font-weight: bold;
        }
        
        /* Listes d'amis */
        .liste-joueurs { list-style: none; padding: 0; margin: 0; }
        .liste-joueurs li {
            display: flex; justify-content: space-between; align-items: center;
            background-color: #3e2510; padding: 10px 15px; border-radius: 4px; margin-bottom: 8px;
        }

        /* Boutons */
        .btn {
            background-color: #7a4a28; color: #fff; border: 1px solid #4a2e14;
            padding: 6px 12px; cursor: pointer; border-radius: 4px; font-weight: bold; text-decoration: none;
        }
        .btn:hover { background-color: #8c5630; }
        .btn-valider { background-color: #27ae60; border-color: #1e7e43; }
        .btn-valider:hover { background-color: #2ecc71; }
        .btn-danger { background-color: #c0392b; border-color: #962d22; }
        .btn-danger:hover { background-color: #e74c3c; }
        
        .btn-retour { position: absolute; top: 20px; left: 20px; }
    </style>
</head>
<body>

    <a href="dashboard.php" class="btn btn-retour">⬅ Tableau de Bord</a>

    <h1>Gestion des Amis</h1>

    <div class="container">
        
        <div class="panel">
            <h2>Ajouter un ami</h2>
            <div class="search-box">
                <input type="text" id="pseudo-recherche" placeholder="Entrez le pseudo exact du joueur...">
                <button class="btn btn-valider" onclick="envoyerDemande()">Envoyer une invitation</button>
            </div>
            <p id="statut-recherche" style="margin-top: 10px; font-style: italic; display: none;"></p>
        </div>

        <div class="panel">
            <h2>Demandes reçues (<?php echo count($demandes); ?>)</h2>
            <ul class="liste-joueurs">
                <?php if(empty($demandes)): ?>
                    <p style="color: #a1887f; font-style: italic;">Aucune invitation en attente.</p>
                <?php else: ?>
                    <?php foreach($demandes as $d): ?>
                        <li id="relation-<?php echo $d['relation_id']; ?>">
                            <span><strong><?php echo htmlspecialchars($d['pseudo']); ?></strong> veut être votre ami</span>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn btn-valider" onclick="gererRelation(<?php echo $d['relation_id']; ?>, 'accepter')">Accepter</button>
                                <button class="btn btn-danger" onclick="gererRelation(<?php echo $d['relation_id']; ?>, 'supprimer')">Refuser</button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <div class="panel">
            <h2>Mes Amis (<?php echo count($amis); ?>)</h2>
            <ul class="liste-joueurs">
                <?php if(empty($amis)): ?>
                    <p style="color: #a1887f; font-style: italic;">Vous n'avez pas encore d'amis dans votre liste.</p>
                <?php else: ?>
                    <?php foreach($amis as $a): ?>
                        <li id="relation-<?php echo $a['relation_id']; ?>">
    <span>🟢 <strong><?php echo htmlspecialchars($a['pseudo_ami']); ?></strong></span>
    <div style="display: flex; gap: 5px;">
        <button class="btn" style="background-color: #d35400; border-color: #a04000;" onclick="lancerDuel(<?php echo $a['relation_id']; ?>)">⚔️ Duel</button>
        <button class="btn btn-danger" onclick="gererRelation(<?php echo $a['relation_id']; ?>, 'supprimer')">Retirer</button>
    </div>
</li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

    </div>

<script>
// Fonction pour envoyer une demande d'ami
function envoyerDemande() {
    const pseudo = document.getElementById('pseudo-recherche').value.trim();
    const statut = document.getElementById('statut-recherche');
    if(!pseudo) return;

    const data = new FormData();
    data.append('pseudo_ami', pseudo);

    fetch('./amis_ajax.php?action=demander', { method: 'POST', body: data })
    .then(r => r.text())
    .then(res => {
        statut.style.display = "block";
        if(res.trim() === 'succes') {
            statut.style.color = "#2ecc71";
            statut.innerText = "Invitation envoyée avec succès à " + pseudo + " !";
            document.getElementById('pseudo-recherche').value = "";
        } else {
            statut.style.color = "#e74c3c";
            statut.innerText = res;
        }
    });
}

// Fonction pour accepter/refuser/supprimer une relation d'ami
function gererRelation(relationId, action) {
    const data = new FormData();
    data.append('relation_id', relationId);

    fetch(`./amis_ajax.php?action=${action}`, { method: 'POST', body: data })
    .then(r => r.text())
    .then(res => {
        if(res.trim() === 'succes') {
            // Supprime ou met à jour visuellement la ligne sans recharger la page
            const ligne = document.getElementById('relation-' + relationId);
            ligne.style.transition = "all 0.3s ease";
            ligne.style.opacity = "0";
            setTimeout(() => { location.reload(); }, 300); // Recharge léger pour replacer dans la bonne section
        } else {
            alert("Une erreur est survenue : " + res);
        }
    });
}

function lancerDuel(idAmi) {
    const data = new FormData();
    data.append('id_ami', idAmi);

    fetch('./jcj_ajax.php?action=defier', { method: 'POST', body: data })
    .then(r => r.json())
    .then(res => {
        if(res.statut === 'succes') {
            alert("Défi envoyé ! Attente de l'adversaire... Ne fermez pas la page.");
            // On commence à surveiller si l'ami accepte
            setInterval(() => {
                fetch('./jcj_ajax.php?action=verifier_defis')
                .then(r => r.json())
                .then(data => {
                    if(data.type === 'lance_accepte') {
                        window.location.href = './plateau.php?match_id=' + data.match_id;
                    }
                });
            }, 2000);
        }
    });
}
</script>
</body>
</html>