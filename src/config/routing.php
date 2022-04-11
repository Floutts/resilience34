<?php
function getPage($db)
{

    $lesPages['accueil'] = "actionAccueil;0";
    $lesPages['validation'] = "actionValidation;0";

    if ($db != null) {
        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        } else {
            $page = 'accueil';
        }

        if (!isset($lesPages[$page])) {
            $page = 'accueil';
        }
        $explose = explode(";", $lesPages[$page]); // Nous découpons la ligne du tableau sur le  // caractère « ; » Le résultat est stocké dans le tableau $explose
        $role = $explose[1]; // Le rôle est dans la 2ème partie du tableau $explose
        if ($role != 0) { // Si mon rôle nécessite une vérification
            if (isset($_SESSION['login'])) {  // Si je me suis authentifié
                if (isset($_SESSION['role'])) {  // Si j’ai bien un rôle
                    if ($role != $_SESSION['role']) { // Si mon rôle ne correspond pas à celui qui est nécessaire //pour voir la page
                        $contenu = 'actionAccueil';  // Je le redirige vers l’accueil, car il n’a pas le bon rôle
                    } else {
                        $contenu = $explose[0];
                        // Je récupère le nom du contrôleur, car il a le bon rôle
                    }
                } else {
                    $contenu = 'actionAccueil';
                }
            } else {
                $contenu = 'actionAccueil';  // Page d’accueil, car il n’est pas authentifié
            }
        } else {
            $contenu = $explose[0]; //  Je récupère le contrôleur, car il n’a pas besoin d’avoir un rôle
        }

    } 
    // $contenu = 'actionAccueil'; 
    return $contenu;
}
?>