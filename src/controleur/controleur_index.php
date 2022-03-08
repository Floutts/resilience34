<?php


function detection_nav() {
    $browser = get_browser(null, true);
    return $browser['browser'];
    //phpinfo();
}

function getLocationInfoByIp(){
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = @$_SERVER['REMOTE_ADDR'];
    $result  = array('country'=>'', 'city'=>'');
    if(filter_var($client, FILTER_VALIDATE_IP)){
        $ip = $client;
    }elseif(filter_var($forward, FILTER_VALIDATE_IP)){
        $ip = $forward;
    }else{
        $ip = $remote;
    }
    $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));    
    if($ip_data && $ip_data->geoplugin_countryName != null){
        $result['country'] = $ip_data->geoplugin_countryCode;
        $result['city'] = $ip_data->geoplugin_city;
    }
    return $result['country'];
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
    echo $ip;
    echo detection_nav();
    var_dump(getLocationInfoByIp());
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
