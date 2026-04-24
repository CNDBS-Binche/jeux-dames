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
</head>
<body>

<script>
let pionSelectionne = null;

// On écoute le clic sur toutes les cases noires
document.querySelectorAll('.black').forEach(caseNoire => {
    caseNoire.addEventListener('click', function() {
        const pion = this.querySelector('.pion');
        
        // CAS 1 : On clique sur un pion pour le sélectionner
        if (pion) {
            // On retire la sélection précédente s'il y en a une
            document.querySelectorAll('.pion').forEach(p => p.classList.remove('selected'));
            
            // On sélectionne le nouveau pion
            pion.classList.add('selected');
            pionSelectionne = {
                element: pion,
                ligne: parseInt(this.dataset.ligne),
                col: parseInt(this.dataset.col),
                couleur: pion.classList.contains('blanc') ? 'blanc' : 'noir'
            };
            console.log("Pion sélectionné en :", pionSelectionne.ligne, pionSelectionne.col);
        } 
        
        // CAS 2 : On a déjà un pion sélectionné et on clique sur une case vide
        else if (pionSelectionne) {
            const destinationLigne = parseInt(this.dataset.ligne);
            const destinationCol = parseInt(this.dataset.col);
            
            // Pour l'instant, on déplace juste le pion visuellement (on verra les règles à l'étape 3)
            this.appendChild(pionSelectionne.element);
            pionSelectionne.element.classList.remove('selected');
            pionSelectionne = null;
        }
    });
});
</script>

<table>
  <thead>
    <tr>
      <td class="coord"></td> <td class="coord">A</td><td class="coord">B</td><td class="coord">C</td><td class="coord">D</td><td class="coord">E</td>
      <td class="coord">F</td><td class="coord">G</td><td class="coord">H</td><td class="coord">I</td><td class="coord">J</td>
      <td class="coord"></td> </tr>
  </thead>
<tbody>
    <?php
    $plateau = [];
    for ($l = 1; $l <= 10; $l++) {
        for ($c = 1; $c <= 10; $c++) {
            $plateau[$l][$c] = 0; // Par défaut vide
            if (($l + $c) % 2 != 0) { // Uniquement sur les cases noires
                if ($l <= 4) $plateau[$l][$c] = 2; // Pions Noirs
                if ($l >= 7) $plateau[$l][$c] = 1; // Pions Blancs
            }
        }
    }

    // 2. Génération automatique du HTML
    for ($ligne = 1; $ligne <= 10; $ligne++) {
        echo "<tr>";
        echo "<td class='coord'>$ligne</td>"; // Coordonnée gauche

        for ($col = 1; $col <= 10; $col++) {
            $typeCase = ($ligne + $col) % 2 == 0 ? 'white' : 'black';
            $contenuCase = "";

            // On place le pion si la case n'est pas vide dans notre tableau
            if ($plateau[$ligne][$col] == 1) {
                $contenuCase = '<div class="pion blanc"></div>';
            } elseif ($plateau[$ligne][$col] == 2) {
                $contenuCase = '<div class="pion noir"></div>';
            }

            // On ajoute des attributs data- pour que le JS puisse identifier la case
            echo "<td class='$typeCase' data-ligne='$ligne' data-col='$col'>";
            echo $contenuCase;
            echo "</td>";
        }

        echo "<td class='coord'>$ligne</td>"; // Coordonnée droite
        echo "</tr>";
    }
    ?>
</tbody>
  <tfoot>
    <tr>
      <td class="coord"></td>
      <td class="coord">A</td><td class="coord">B</td><td class="coord">C</td><td class="coord">D</td><td class="coord">E</td>
      <td class="coord">F</td><td class="coord">G</td><td class="coord">H</td><td class="coord">I</td><td class="coord">J</td>
      <td class="coord"></td>
    </tr>
  </tfoot>
</table>

</body>
</html>