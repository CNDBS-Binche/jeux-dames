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
        .pion.selected {
            border: 3px solid #f1c40f !important;
            box-shadow: 0 0 15px #f1c40f;
        }
        /* Style pour l'affichage du tour */
        #status-bar {
            text-align: center;
            margin: 20px;
            font-family: Arial, sans-serif;
            font-size: 20px;
            color: white;
            background: rgba(0,0,0,0.5);
            padding: 10px;
            border-radius: 8px;
        }
        .tour-blanc { color: #f0d9b5; font-weight: bold; }
        .tour-noir { color: #000; text-shadow: 0 0 5px white; font-weight: bold; }

        /* Le point vert translucide */
    .aide-coup {
        width: 20px;
        height: 20px;
        background-color: rgba(46, 204, 113, 0.5); /* Vert translucide */
        border-radius: 50%;
        margin: auto;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none; /* Pour que le clic passe à travers et atteigne la case */
        z-index: 5;
    }
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
// Fonction pour nettoyer les points verts
function nettoyerAide() {
    document.querySelectorAll('.aide-coup').forEach(el => el.remove());
}

// Fonction pour afficher les coups possibles
function montrerCoupsPossibles(pionData) {
    nettoyerAide();
    
    const directions = [];
    // Un pion normal avance selon sa couleur, une Dame (si tu l'implémentes) dans les deux
    if (pionData.couleur === 'blanc') directions.push({l: -1, c: -1}, {l: -1, c: 1});
    else directions.push({l: 1, c: -1}, {l: 1, c: 1});

    // On regarde aussi les sauts (prises) possibles dans toutes les directions
    const directionsPrise = [{l: -2, c: -2}, {l: -2, c: 2}, {l: 2, c: -2}, {l: 2, c: 2}];

    // 1. Tester déplacements simples
    directions.forEach(dir => {
        const cibleL = pionData.ligne + dir.l;
        const cibleC = pionData.col + dir.c;
        const caseCible = document.querySelector(`[data-ligne="${cibleL}"][data-col="${cibleC}"].black`);
        
        if (caseCible && !caseCible.querySelector('.pion')) {
            ajouterPointVert(caseCible);
        }
    });

    // 2. Tester les prises (sauts)
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

function ajouterPointVert(parent) {
    const point = document.createElement('div');
    point.className = 'aide-coup';
    parent.appendChild(point);
}

document.querySelectorAll('.black').forEach(caseNoire => {
    caseNoire.addEventListener('click', function() {
        const pion = this.querySelector('.pion');
        
        if (pion) {
            const couleurPion = pion.classList.contains('blanc') ? 'blanc' : 'noir';
            if (couleurPion !== tourActuel) return;

            document.querySelectorAll('.pion').forEach(p => p.classList.remove('selected'));
            pion.classList.add('selected');
            
            pionSelectionne = {
                element: pion,
                ligne: parseInt(this.dataset.ligne),
                col: parseInt(this.dataset.col),
                couleur: couleurPion
            };
            
            // AFFICHER LES COUPS POSSIBLES
            montrerCoupsPossibles(pionSelectionne);
        } 
        else if (pionSelectionne) {
            const destLigne = parseInt(this.dataset.ligne);
            const destCol = parseInt(this.dataset.col);
            const diffLigne = destLigne - pionSelectionne.ligne;
            const diffCol = Math.abs(destCol - pionSelectionne.col);

            let mouvementValide = false;

            // Vérification si la case cliquée a un point vert (donc est valide)
            if (this.querySelector('.aide-coup')) {
                mouvementValide = true;
                
                // Si c'est une prise, on retire le pion mangé
                if (Math.abs(diffLigne) === 2) {
                    const sautLigne = pionSelectionne.ligne + (diffLigne / 2);
                    const sautCol = pionSelectionne.col + (destCol - pionSelectionne.col) / 2;
                    document.querySelector(`[data-ligne="${sautLigne}"][data-col="${sautCol}"] .pion`).remove();
                }
            }

            if (mouvementValide) {
                nettoyerAide();
                this.appendChild(pionSelectionne.element);
                pionSelectionne.element.classList.remove('selected');
                
                if ((pionSelectionne.couleur === 'blanc' && destLigne === 1) || 
                    (pionSelectionne.couleur === 'noir' && destLigne === 10)) {
                    pionSelectionne.element.classList.add('dame');
                    pionSelectionne.element.innerHTML = "👑";
                }
                
                tourActuel = (tourActuel === 'blanc') ? 'noir' : 'blanc';
                statusEl.innerText = (tourActuel === 'blanc') ? 'Blancs' : 'Noirs';
                statusEl.className = 'tour-' + tourActuel;
                pionSelectionne = null;
            }
        }
    });
});
</script>

</body>
</html>