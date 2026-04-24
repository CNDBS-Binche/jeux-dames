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

<table>
  <thead>
    <tr>
      <td class="coord"></td> <td class="coord">A</td><td class="coord">B</td><td class="coord">C</td><td class="coord">D</td><td class="coord">E</td>
      <td class="coord">F</td><td class="coord">G</td><td class="coord">H</td><td class="coord">I</td><td class="coord">J</td>
      <td class="coord"></td> </tr>
  </thead>
<tbody>
    <?php
    // 1. Initialisation du plateau en mémoire (0=vide, 1=blanc, 2=noir)
    // Dans un vrai projet, ceci viendra d'une base de données ou d'une session
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