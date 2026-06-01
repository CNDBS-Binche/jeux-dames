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
        /* Effet au survol discret */
        .pion {
            transition: box-shadow 0.2s ease-out;
            cursor: pointer;
        }

        .pion.blanc:hover {
            box-shadow: 0 0 12px 3px rgba(0, 0, 0, 0.3);
        }

        .pion.noir:hover {
            box-shadow: 0 0 12px 3px rgba(0, 0, 0, 0.5), 0 0 6px 1px rgba(255, 255, 255, 0.2);
        }

        /* --- CONFIGURATION DE LA DAME (DOUBLE PION) --- */
        /* ::after dessine le deuxième pion empilé au centre */
        .pion.dame::after {
            content: "";
            position: absolute;
            top: 15%;
            left: 15%;
            width: 70%;
            height: 70%;
            border-radius: 50%;
            box-sizing: border-box;
            z-index: 2;
        }

        .pion.dame.blanc::after {
            background-color: white;
            border: 2px solid #b3b3b3;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
        }

        .pion.dame.noir::after {
            background-color: #222;
            border: 2px solid #444;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }

        /* --- AJOUT : LE LOGO DE LA COURONNE AU MILIEU --- */
        /* ::before place l'émoji couronne au-dessus du double pion */
        .pion.dame::before {
            content: "👑";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            
            /* Alignement flexbox parfait au centre exact */
            display: flex;
            justify-content: center;
            align-items: center;
            
            /* Taille de la couronne proportionnelle au pion */
            font-size: 1.2rem; 
            z-index: 3;
            
            /* Empêche la sélection de texte sur l'émoji */
            user-select: none; 
            pointer-events: none;
        }
    </style>
</head>
<body>

<div id="status-bar">
    <span id="joueur-actif" class="tour-blanc" style="display: none;">Blancs</span>
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
                    if ($l <= 4) $plateau[$l][$c] = 1; 
                    if ($l >= 7) $plateau[$l][$c] = 2; 
                }
            }
        }

        for ($ligne = 10; $ligne >= 1; $ligne--) {
            echo "<tr>";
            echo "<td class='coord'>$ligne</td>";
            for ($col = 1; $col <= 10; $col++) {
                $typeCase = ($ligne + $col) % 2 == 0 ? 'white' : 'black';
                $contentsCase = "";
                if ($plateau[$ligne][$col] == 1) $contentsCase = '<div class="pion blanc"></div>';
                elseif ($plateau[$ligne][$col] == 2) $contentsCase = '<div class="pion noir"></div>';

                echo "<td class='$typeCase' data-ligne='$ligne' data-col='$col'>$contentsCase</td>";
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
document.addEventListener('DOMContentLoaded', function() {
    let pionSelectionne = null;
    let tourActuel = 'blanc'; 
    const statusEl = document.getElementById('joueur-actif');

    document.body.classList.add('tour-actif-blanc');

    console.log("Le script de dames est chargé et prêt.");

    function nettoyerAide() {
        document.querySelectorAll('.aide-coup').forEach(el => el.remove());
    }

    function ajouterPointVert(parent) {
        if (!parent.querySelector('.aide-coup')) { 
            const point = document.createElement('div');
            point.className = 'aide-coup';
            parent.appendChild(point);
        }
    }

    function montrerCoupsPossibles(pionData) {
        nettoyerAide();
        const directionsVisibles = [];
        
        if (pionData.element.classList.contains('dame')) {
            directionsVisibles.push({l: 1, c: -1}, {l: 1, c: 1}, {l: -1, c: -1}, {l: -1, c: 1});
        } else {
            if (pionData.couleur === 'blanc') directionsVisibles.push({l: 1, c: -1}, {l: 1, c: 1});
            else directionsVisibles.push({l: -1, c: -1}, {l: -1, c: 1});
        }

        directionsVisibles.forEach(dir => {
            const cibleL = pionData.ligne + dir.l;
            const cibleC = pionData.col + dir.c;
            const caseCible = document.querySelector(`[data-ligne="${cibleL}"][data-col="${cibleC}"].black`);
            
            if (caseCible && !caseCible.querySelector('.pion')) {
                ajouterPointVert(caseCible);
            }
        });

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

    const casesNoires = document.querySelectorAll('.black');
    casesNoires.forEach(caseNoire => {
        caseNoire.addEventListener('click', function() {
            let pion = this.querySelector('.pion');
            
            if (pion) {
                const couleurPion = pion.classList.contains('blanc') ? 'blanc' : 'noir';
                
                if (couleurPion !== tourActuel) {
                    return; 
                }

                document.querySelectorAll('.pion').forEach(p => p.classList.remove('selected'));
                pion.classList.add('selected');
                
                pionSelectionne = {
                    element: pion,
                    ligne: parseInt(this.dataset.ligne),
                    col: parseInt(this.dataset.col),
                    couleur: couleurPion
                };
                
                montrerCoupsPossibles(pionSelectionne);
            } 
            
            else if (pionSelectionne) {
                if (!this.querySelector('.aide-coup')) {
                    return;
                }

                const destLigne = parseInt(this.dataset.ligne);
                const destCol = parseInt(this.dataset.col);
                const diffLigne = destLigne - pionSelectionne.ligne;

                if (Math.abs(diffLigne) === 2) {
                    const sautLigne = pionSelectionne.ligne + (diffLigne / 2);
                    const sautCol = pionSelectionne.col + (destCol - pionSelectionne.col) / 2;
                    const caseSautee = document.querySelector(`[data-ligne="${sautLigne}"][data-col="${sautCol}"]`);
                    const pionSaute = caseSautee.querySelector('.pion');
                    if (pionSaute) pionSaute.remove();
                }

                this.appendChild(pionSelectionne.element);
                pionSelectionne.element.classList.remove('selected');
                nettoyerAide();
                
                if (!pionSelectionne.element.classList.contains('dame')) {
                    if ((pionSelectionne.couleur === 'blanc' && destLigne === 10) || 
                        (pionSelectionne.couleur === 'noir' && destLigne === 1)) {
                        
                        pionSelectionne.element.classList.add('dame');
                    }
                }
                
                tourActuel = (tourActuel === 'blanc') ? 'noir' : 'blanc';
                statusEl.innerText = (tourActuel === 'blanc') ? 'Blancs' : 'Noirs';
                statusEl.className = 'tour-' + tourActuel;

                // Gestion dynamique du dégradé de l'arrière-plan
                if (tourActuel === 'blanc') {
                    document.body.classList.add('tour-actif-blanc');
                } else {
                    document.body.classList.remove('tour-actif-blanc');
                }

                pionSelectionne = null;
            }
        });
    });
});
</script>
</body>
</html>