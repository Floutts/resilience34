<?php

function actionAccueil($twig) {
    echo $twig->render('index.html.twig', array());
}

function actionMentions($twig){
    echo $twig->render('mentions.html.twig',array());
}

function actionApropos($twig){
    echo $twig->render('apropos.html.twig',array());
}

function actionMaintenance($twig){
    echo $twig->render('maintenance.html.twig',array());
}

function actionDeconnexion($twig){
    session_unset();
    session_destroy();
    header("Location:index.php");
}

function detection_nav() {
    $browser = get_browser(null, true);
    print_r($browser['browser']);
    //phpinfo();
}


function actionConnexion($twig,$db){
    $form = array();
    $form['valide'] = true;
    $etape = isset($_POST['etape']) ? $_POST['etape'] : 1;
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    //echo $ip;
    echo detection_nav();
    if (isset($_POST['btConnexion']) && $etape = 1){
        $email = $_POST['email'];
        $mdp = $_POST['mdp'];

        //$utilisateur = new Utilisateur($db);
        //$unUtilisateur = $utilisateur->connect($email);
        // if ($unUtilisateur!=null){
        //     if(!password_verify($mdp,$unUtilisateur['mdp'])){
        //         $form['valide'] = false;
        //         $form['message'] = 'Login ou mot de passe incorrect';
        //     }
        //     else{
        //         $_SESSION['login'] = $email;
        //         $_SESSION['role'] = $unUtilisateur['idRole'];
        //     }
        // }
        // else{
        //     $form['valide'] = false;
        //     $form['message'] = 'Login ou mot de passe incorrect';

        // }

        # Include packages
        require_once(__DIR__ . '/../lib/vendor/autoload.php');

        # Create the 2FA class
        $google2fa = new PragmaRX\Google2FA\Google2FA();

        # Print a user secret for user to enter into their phone. 
        # The application needs to persist this somewhere safely where other users can't get it.
        $userSecret = $google2fa->generateSecretKey();

        print "Please enter the following secret into your phone:" . PHP_EOL .  $userSecret . PHP_EOL;

        $etape=2;
    }
    echo $twig->render('connexion.html.twig',array('form'=>$form,'etape'=>$etape));
}

function actionInscription($twig,$db){
    $form = array();
    if (isset($_POST['btInscrire'])){
        $nbUnique = uniqid();
        $email = $_POST['email'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $mdp = $_POST['mdp'];
        $mdp2 =$_POST['mdp2'];
        $role = $_POST['role'];
        $photo=NULL;
        if(isset($_FILES['photo'])){
            if(!empty($_FILES['photo']['name'])){
                $extensions_ok = array('png', 'gif', 'jpg', 'jpeg');
                $taille_max = 500000;
                $dest_dossier = '/var/www/html/symfony4-4060/public/Englearn/web/images/';
                if( !in_array( substr(strrchr($_FILES['photo']['name'], '.'), 1), $extensions_ok ) ){
                    echo 'Veuillez sélectionner un fichier de type png, gif ou jpg !';
                }
                else{
                    if( file_exists($_FILES['photo']['tmp_name'])&& (filesize($_FILES['photo']
                        ['tmp_name'])) > $taille_max){
                        echo 'Votre fichier doit faire moins de 500Ko !';
                    }
                    else{$photo = basename($_FILES['photo']['name']);
                        // enlever les accents

                        $photo=strtr($photo,'ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ','AAA
AAACEEEEIIIIOOOOOUUUUYaaaaaaceeeeiiiioooooouuuuyy');
                        // remplacer les caractères autres que lettres, chiffres et point par _
                        $photo = preg_replace('/([^.a-z0-9]+)/i', '_', $photo);
                        // copie du fichier
                        move_uploaded_file($_FILES['photo']['tmp_name'], $dest_dossier.$photo);
                    }
                }
            }
        }
        $form['valide'] = true;
        if ($mdp!=$mdp2){
            $form['valide'] = false;
            $form['message'] = 'Les mots de passe sont différents';
        }
        else{
            $utilisateur = new Utilisateur($db);
            $exec = $utilisateur->insert($email, password_hash($mdp,PASSWORD_DEFAULT), $role, $nom, $prenom,$nbUnique,$photo);
            if (!$exec){
                $form['valide'] = false;
                $form['message'] = 'Problème d\'insertion dans la table utilisateur ';
            }

        }
        $form['email'] = $email;
        $form['nom'] = $nom;
        $form['prenom'] = $prenom;
        $form['role'] = $role;
    }
    echo $twig->render('inscription.html.twig',array('form'=>$form));
}



