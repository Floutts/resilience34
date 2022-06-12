<?php
function actionValidation($twig,$db){
    $form = array();
    $etape = 1;
    if(isset($_GET['email'])){
        $utilisateur = new Utilisateur($db);
        $unUtilisateur = $utilisateur->selectByUsername($_GET['email']);
        if ($unUtilisateur!=null){
            if ( $unUtilisateur['uniqid'] == $_GET['nbUnique']){
                $etape = 3;
            }
            else{
                $form['message'] = 'Validation échouée (nbUnique)';
            }
        }
        else{
            $form['message'] = 'Utilisateur incorrect';
        }
    }
    else{
        $form['message'] = 'Utilisateur non précisé';
    }
    echo $twig->render('index.html.twig', array('form'=>$form, 'etape'=>$etape));
}
?>