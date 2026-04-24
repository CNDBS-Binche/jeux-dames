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
        /* Ajoute ceci dans ton style.css pour voir la sélection */
        .pion.selected {
            border: 3px solid #f1c40f !important;
            box-shadow: 0 0 15px #f1c40f;
        }
    </style>
</head>
<body>

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
let pionSelectionne = null;

document.querySelectorAll('.black').forEach(caseNoire => {
    caseNoire.addEventListener('click', function() {
        const pion = this.querySelector('.pion');
        
        // --- 1. SÉLECTION D'UN PION ---
        if (pion) {
            document.querySelectorAll('.pion').forEach(p => p.classList.remove('selected'));
            pion.classList.add('selected');
            pionSelectionne = {
                element: pion,
                ligne: parseInt(this.dataset.ligne),
                col: parseInt(this.dataset.col),
                couleur: pion.classList.contains('blanc') ? 'blanc' : 'noir'
            };
        } 
        
        // --- 2. TENTATIVE DE DÉPLACEMENT ---
        else if (pionSelectionne) {
            const destLigne = parseInt(this.dataset.ligne);
            const destCol = parseInt(this.dataset.col);
            
            const diffLigne = destLigne - pionSelectionne.ligne;
            const diffCol = Math.abs(destCol - pionSelectionne.col);

            let mouvementValide = false;

            // Déplacement simple (1 case en diagonale)
            if (diffCol === 1) {
                if (pionSelectionne.couleur === 'blanc' && diffLigne === -1) mouvementValide = true;
                if (pionSelectionne.couleur === 'noir' && diffLigne === 1) mouvementValide = true;
            }
            
            // Prise d'un pion (saut de 2 cases)
            else if (diffCol === 2 && Math.abs(diffLigne) === 2) {
                const sautLigne = pionSelectionne.ligne + (diffLigne / 2);
                const sautCol = pionSelectionne.col + (destCol - pionSelectionne.col) / 2;
                const caseSautee = document.querySelector(`[data-ligne="${sautLigne}"][data-col="${sautCol}"]`);
                const pionSaute = caseSautee.querySelector('.pion');

                // On vérifie s'il y a un pion adverse sur la case sautée
                if (pionSaute && !pionSaute.classList.contains(pionSelectionne.couleur)) {
                    pionSaute.remove(); // On mange le pion !
                    mouvementValide = true;
                }
            }

            // --- 3. EXÉCUTION DU MOUVEMENT ---
            if (mouvementValide) {
                this.appendChild(pionSelectionne.element);
                pionSelectionne.element.classList.remove('selected');
                
                // Promotion en Dame (si arrive au bout)
                if ((pionSelectionne.couleur === 'blanc' && destLigne === 1) || 
                    (pionSelectionne.couleur === 'noir' && destLigne === 10)) {
                    pionSelectionne.element.classList.add('dame');
                    pionSelectionne.element.innerHTML = "👑"; // Optionnel : petit visuel
                }
                
                pionSelectionne = null;
            }
        }
    });
});
</script>

</body>
</html>