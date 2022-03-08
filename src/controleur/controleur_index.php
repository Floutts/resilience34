<?php


function detection_nav() {
    $browser = get_browser(null, true);
    print_r($browser['browser']);
    //phpinfo();
}


function actionAccueil($twig,$db){
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
    echo $twig->render('index.html.twig',array('form'=>$form,'etape'=>$etape));
}
