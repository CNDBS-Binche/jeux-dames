<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php?erreur=acces_refuse');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="style.css">
    <title>Damier de Dames 10x10</title>
<style>
    /* CSS pour le contour de sélection (maintenant jaune/orangé vif) */
    .pion.selected {
        outline: 4px solid #f1c40f !important; /* outline est plus visible que border */
        outline-offset: 2px;
        box-shadow: 0 0 20px #f1c40f !important;
        transform: translate(-50%, -50%) scale(1.1) !important; /* Léger grossissement */
        transition: transform 0.2s ease, outline 0.2s ease;
    }

    /* Le point vert translucide */
    .aide-coup {
        width: 20px;
        height: 20px;
        background-color: rgba(46, 204, 113, 0.6); /* Vert translucide légèrement plus opaque */
        border-radius: 50%;
        margin: auto;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none; /* Crucial pour que le clic passe à travers */
        z-index: 5;
    }

    /* Style pour l'affichage du tour (pour correspondre à ton image) */
    #status-bar {
        text-align: center;
        margin: 20px auto;
        font-family: Arial, sans-serif;
        font-size: 20px;
        color: white;
        background: #1e252b; /* Même couleur que sur ton image */
        padding: 15px;
        border-radius: 8px;
        width: fit-content;
        box-shadow: 0 4px 10px rgba(0,0,0,0.5);
    }
    .tour-blanc { color: #f0d9b5; font-weight: bold; }
    .tour-noir { color: #c0c0c0; font-weight: bold; } /* Gris clair pour les noirs */
</style>
</head>
<body>

<div id="status-bar">
    C'est au tour des : <span id="joueur-actif" class="tour-blanc">Blancs</span>
</div>

<table>
    <thead>
        <tr>
            <td class="coord"></td><td class="coord">A</td><td class="coord">B</td><td class="coord">C</td><td class="coord">D</td><td class="coord">E</td><td class="coord">F</td><td class="coord">G</td><td class="coord">H</td><td class="coord">I</td><td class="coord">J</td><td class="coord"></td>
        </tr>
    </thead>
    <tbody>
        <?php
        $plateau = [];
        for ($l = 1; $l <= 10; $l++) {
            for ($c = 1; $c <= 10; $c++) {
                $plateau[$l][$c] = 0;
                if (($l + $c) % 2 != 0) {
                    if ($l <= 4) $plateau[$l][$c] = 2; // Noirs
                    if ($l >= 7) $plateau[$l][$c] = 1; // Blancs
                }
            }
        }

        for ($ligne = 1; $ligne <= 10; $ligne++) {
            echo "<tr>";
            echo "<td class='coord'>$ligne</td>";
            for ($col = 1; $col <= 10; $col++) {
                $typeCase = ($ligne + $col) % 2 == 0 ? 'white' : 'black';
                $contenuCase = "";
                if ($plateau[$ligne][$col] == 1) $contenuCase = '<div class="pion blanc"></div>';
                elseif ($plateau[$ligne][$col] == 2) $contenuCase = '<div class="pion noir"></div>';

                echo "<td class='$typeCase' data-ligne='$ligne' data-col='$col'>$contenuCase</td>";
            }
            echo "<td class='coord'>$ligne</td>";
            echo "</tr>";
        }
        ?>

        
    </tbody>
    <tfoot>
        <tr>
            <td class="coord"></td><td class="coord">A</td><td class="coord">B</td><td class="coord">C</td><td class="coord">D</td><td class="coord">E</td><td class="coord">F</td><td class="coord">G</td><td class="coord">H</td><td class="coord">I</td><td class="coord">J</td><td class="coord"></td>
        </tr>
    </tfoot>
</table>

<script>
// On englobe tout dans un écouteur DOMContentLoaded pour être SÛR que le HTML est là
document.addEventListener('DOMContentLoaded', function() {
    
    let pionSelectionne = null;
    let tourActuel = 'blanc'; // Les blancs commencent toujours
    const statusEl = document.getElementById('joueur-actif');

    console.log("Le script de dames est chargé et prêt.");

    // --- FONCTIONS D'AIDE ---
    function nettoyerAide() {
        document.querySelectorAll('.aide-coup').forEach(el => el.remove());
    }

    function ajouterPointVert(parent) {
        if (!parent.querySelector('.aide-coup')) { // Éviter les doublons
            const point = document.createElement('div');
            point.className = 'aide-coup';
            parent.appendChild(point);
        }
    }

    // --- FONCTION DE CALCUL DES COUPS POSSIBLES ---
    function montrerCoupsPossibles(pionData) {
        nettoyerAide();
        const directionsVisibles = [];
        
        // Un pion normal avance, une dame (non gérée ici) avance et recule
        // Tes noirs sont en haut (lignes 1-4), tes blancs en bas (lignes 7-10)
        if (pionData.couleur === 'blanc') directionsVisibles.push({l: -1, c: -1}, {l: -1, c: 1});
        else directionsVisibles.push({l: 1, c: -1}, {l: 1, c: 1});

        // Test déplacements simples
        directionsVisibles.forEach(dir => {
            const cibleL = pionData.ligne + dir.l;
            const cibleC = pionData.col + dir.c;
            const caseCible = document.querySelector(`[data-ligne="${cibleL}"][data-col="${cibleC}"].black`);
            
            if (caseCible && !caseCible.querySelector('.pion')) {
                ajouterPointVert(caseCible);
            }
        });

        // Test des prises (obligatoires et dans toutes les directions pour les pions au 10x10)
        const directionsPrise = [{l: -2, c: -2}, {l: -2, c: 2}, {l: 2, c: -2}, {l: 2, c: 2}];
        directionsPrise.forEach(dir => {
            const cibleL = pionData.ligne + dir.l;
            const cibleC = pionData.col + dir.c;
            const interL = pionData.ligne + (dir.l / 2);
            const interC = pionData.col + (dir.c / 2);
            
            const caseCible = document.querySelector(`[data-ligne="${cibleL}"][data-col="${cibleC}"].black`);
            const caseInter = document.querySelector(`[data-ligne="${interL}"][data-col="${interC}"]`);
            
            if (caseCible && !caseCible.querySelector('.pion') && caseInter) {
                const pionInter = caseInter.querySelector('.pion');
                if (pionInter && !pionInter.classList.contains(pionData.couleur)) {
                    ajouterPointVert(caseCible);
                }
            }
        });
    }

    // --- GESTIONNAIRE DE CLIC PRINCIPAL ---
    // On attache l'écouteur aux cases noires après chargement du DOM
    const casesNoires = document.querySelectorAll('.black');
    console.log(casesNoires.length + " cases noires trouvées.");

    casesNoires.forEach(caseNoire => {
        caseNoire.addEventListener('click', function() {
            const pion = this.querySelector('.pion');
            console.log("Clic sur case :", this.dataset.ligne, this.dataset.col, "| Pion présent :", !!pion);
            
            // --- 1. SÉLECTION D'UN PION ---
            if (pion) {
                const couleurPion = pion.classList.contains('blanc') ? 'blanc' : 'noir';
                
                // VERIFICATION DU TOUR
                if (couleurPion !== tourActuel) {
                    console.log("Ce n'est pas votre tour ! Attendez les :", tourActuel);
                    return; 
                }

                // Visuel de sélection
                document.querySelectorAll('.pion').forEach(p => p.classList.remove('selected'));
                pion.classList.add('selected');
                
                pionSelectionne = {
                    element: pion,
                    ligne: parseInt(this.dataset.ligne),
                    col: parseInt(this.dataset.col),
                    couleur: couleurPion
                };
                
                // Calcul et affichage des ronds verts
                montrerCoupsPossibles(pionSelectionne);
            } 
            
            // --- 2. TENTATIVE DE DÉPLACEMENT SUR UNE CASE VIDE ---
            else if (pionSelectionne) {
                // SÉCURITÉ : On ne peut cliquer que sur une case qui a un point vert
                if (!this.querySelector('.aide-coup')) {
                    console.log("Coup invalide. Cliquez sur un point vert.");
                    return;
                }

                const destLigne = parseInt(this.dataset.ligne);
                const destCol = parseInt(this.dataset.col);
                const diffLigne = destLigne - pionSelectionne.ligne;
                const diffCol = Math.abs(destCol - pionSelectionne.col);

                // Si c'est une prise (saut de 2), on retire le pion mangé
                if (Math.abs(diffLigne) === 2) {
                    const sautLigne = pionSelectionne.ligne + (diffLigne / 2);
                    const sautCol = pionSelectionne.col + (destCol - pionSelectionne.col) / 2;
                    const caseSautee = document.querySelector(`[data-ligne="${sautLigne}"][data-col="${sautCol}"]`);
                    const pionSaute = caseSautee.querySelector('.pion');
                    if (pionSaute) pionSaute.remove();
                }

                // EXÉCUTION DU MOUVEMENT VISUEL
                this.appendChild(pionSelectionne.element);
                pionSelectionne.element.classList.remove('selected');
                nettoyerAide();
                
                // Promotion en Dame (si arrive au bout)
                if ((pionSelectionne.couleur === 'blanc' && destLigne === 1) || 
                    (pionSelectionne.couleur === 'noir' && destLigne === 10)) {
                    pionSelectionne.element.classList.add('dame');
                    pionSelectionne.element.innerHTML = "👑"; // Visuel temporaire
                }
                
                // CHANGEMENT DE TOUR
                tourActuel = (tourActuel === 'blanc') ? 'noir' : 'blanc';
                
                // Mise à jour de la barre de statut (visuel de ton image)
                statusEl.innerText = (tourActuel === 'blanc') ? 'Blancs' : 'Noirs';
                statusEl.className = 'tour-' + tourActuel;

                console.log("Mouvement réussi. C'est aux :", tourActuel);
                pionSelectionne = null;
            }
        });
    });
});
</script>
</body>
</html>