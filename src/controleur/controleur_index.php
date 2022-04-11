<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require_once(__DIR__ .'/../lib/vendor/autoload.php');
define('GMailUser', 'resilience34.LeChatelet@gmail.com'); // utilisateur Gmail
define('GMailPWD', 'resilience34'); // Mot de passe Gmail

function detection_nav() {
    $browser = get_browser(null, true);
    return $browser['browser'];
    //phpinfo();
}

// Provenance de l'adresse IP
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
    $result['ip_address'] = $ip;
    $ip_data = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=".$ip));    
    if($ip_data && $ip_data->geoplugin_countryName != null){
        $result['country'] = $ip_data->geoplugin_countryCode;
        $result['city'] = $ip_data->geoplugin_city;
    }

    return $result;
}

function smtpMailer($to, $subject, $body) {
    mail($to,$subject,$body);
}

function actionAccueil($twig,$db){
    $form = array();
    $form['valide'] = true;
    $ip = getLocationInfoByIp();
    $country = $ip['country'];
    $nbUnique = uniqid();

    if ($country == 'FR'){   
    //$email = 'fabienbayon@yahoo.fr';
    $etape = isset($_POST['etape']) ? $_POST['etape'] : 1;
    echo "etape : ";
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    if (isset($_POST['btConnexion']) && $etape == 1){
        $email = $_POST['username']; // on recupere l'email saisie
        $utilisateur = new Utilisateur($db);

        $unUtilisateur = $utilisateur->selectByUsername($email);
        if(/* utilisateur dans ADDS */ true && $unUtilisateur == null){

             # Include packages
            require_once(__DIR__ . '/../lib/vendor/autoload.php');

            # Create the 2FA class
            $google2fa = new PragmaRX\Google2FA\Google2FA();

            # Print a user secret for user to enter into their phone. 
            # The application needs to persist this somewhere safely where other users can't get it.
            $userSecret = $google2fa->generateSecretKey();

            $exec = $utilisateur->insert($email,$ip,detection_nav(),"fabienbayon@yahoo.fr",$userSecret, $nbUnique);
            if($exec){
                $form['valide'] = false;
                $form['message'] = 'Votre utilisateur est bien inséré, veuillez regarder vos mails';
                $_SESSION['username'] = $unUtilisateur['username'];
                $serveur = $_SERVER['HTTP_HOST'];
                $script = $_SERVER["SCRIPT_NAME"];
                $message = "
                <html>
                    <head>
                    Nous venons de detecter qu'il s'agit de votre premiere connexion.
                    \n Pour vous connecter au site, veuillez saisir la clee suivante dans l'application Google Authenticator : " .$userSecret. "  
                    </head>
                    <body>
                    Entrez ensuite le code a 6 chiffres sur l'application lorsque celui-ci est demande par le site.   
                    \n Pour toutes questions supplementaire, veuillez vous referer a la documentation sur l'authentification a double facteur.    
                    </body>
                </html>
                 ";
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=iso-8859-1';
                mail($email, 'Google Athenticator Resilience34', $message, implode("\n",$headers));
           }else{
            $form['valide'] = false;
            $form['message'] = 'Problème d\'insertion dans la base de données';
            }

        }elseif(/* utilisateur dans ADDS */ true && $unUtilisateur != null){
            $_SESSION['username'] = $unUtilisateur['username'];
            $etape = 2;
        }
    }
    elseif(isset($_POST['btConnexion']) && $etape == 2){
        $ip = getLocationInfoByIp();
        $utilisateur = new Utilisateur($db);
        $unUtilisateur = $utilisateur->selectByUsername($_SESSION['username']);
        $nbUnique = $unUtilisateur['uniqid'];
        $email = $_SESSION['username'];
        # Include packages
        require_once(__DIR__ . '/../lib/vendor/autoload.php');

        # Create the 2FA class
        $google2fa = new PragmaRX\Google2FA\Google2FA();

        # Get the 2FA code from the user. If this is a website, you would fetch from a posted field instead.
        $code = $_POST['code'];

        # Fetch/load the user secret in whatever way you do.
        $userSecret = $unUtilisateur['googleKey'];

        # Verify the code is correct against our persisted user secret.
        # This returns true if correct, false if not.
        $valid = $google2fa->verifyKey($userSecret, $code); 

        if($valid){
            echo "Authentication PASSED!";
            $etape = 3;
            if($unUtilisateur['ip_address'] != $ip['ip_address']){
                $serveur = $_SERVER['HTTP_HOST'];
                $script = $_SERVER["SCRIPT_NAME"];
                $message = "
                <html>
                    <head>
                    Attention, nous venons de detecter une connexion via un nouvel appareil
                    </head>
                    <body>
                    Voici l'addresse IP de celui-ci : " + $ip['ip_address'] + "
                    Si ce n'est pas vous, veuillez le signaler au plus vite
                    </body>
                </html>
                 ";
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=iso-8859-1';
                mail($email, 'Information Resilience34', $message, implode("\n",$headers));
                $form['valide'] = false;
                $form['message'] = 'l\'adresse ip est différente de celle de votre 1ere connexion';
            }
            if($unUtilisateur['browser'] != detection_nav()){
                $serveur = $_SERVER['HTTP_HOST'];
                $script = $_SERVER["SCRIPT_NAME"];
                $message = "
                <html>
                    <head>
                    Bonjour, nous avons detecter une connexion via un navigateur different,
                    </head>
                    <body>
                        Veuillez confirmer votre identite en cliquant sur le lien ci dessous pour continuer :
                    <a href='http://$serveur$script?page=validation&email=$email&nbUnique=$nbUnique'> http://$serveur$script?page=modifMdp&email=$email&nbUnique=$nbUnique </a>
                    </body>
                </html>
                 ";
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=iso-8859-1';
                mail($email, 'Information Resilience34', $message, implode("\n",$headers));
                $etape = 1;
                $form['valide'] = false;
                $form['message'] = 'le navigateur est différent de celui de votre 1ere connexion';
             }
        }else{
            echo "Authentication FAILED!";
            $form['valide'] = false;
            $form['message'] = 'Code incorrect';
        }
        print PHP_EOL;
    }
}else{
    echo "Pays Different de la france";
}
    echo $twig->render('index.html.twig',array('form'=>$form,'etape'=>$etape));
}
