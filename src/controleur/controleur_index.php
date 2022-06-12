<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require_once(__DIR__ .'/../lib/vendor/autoload.php');
define('GMailUser', 'resilience34.LeChatelet@gmail.com'); // utilisateur Gmail
define('GMailPWD', 'resilience34'); // Mot de passe Gmail

function detection_nav() {
    // $browser = get_browser(null, true);
    $browser = 'chrome';
    //return $browser['browser'];
    return $browser;
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

function isUserInADDS($user,$adds){
    foreach($adds as $utilisateur){
        if($utilisateur["samaccountname"][0] == $user){
            return true;
        }
    }
    return false;
}

function actionAccueil($twig,$db){
    $form = array();
    $ip = getLocationInfoByIp();
    $country = $ip['country'];
    $nbUnique = uniqid();
    if ($country == 'FR'){   
    //$email = 'fabienbayon@yahoo.fr';
    $etape = isset($_POST['etape']) ? $_POST['etape'] : 1;
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    $adds = [];
    array_push($adds,array("samaccountname"=>array("count"=>1,0=>"Administrateur"),0=>"samaccountname","count"=>1,"dn"=>"CN=Administrateur,CN=Users,DC=mspr,DC=local"));
    array_push($adds,array("samaccountname"=>array("count"=>1,0=>"Invité"),0=>"samaccountname","count"=>1,"dn"=>"CN=Invité,CN=Users,DC=mspr,DC=local"));
    array_push($adds,array("samaccountname"=>array("count"=>1,0=>"DefaultAccount"),0=>"samaccountname","count"=>1,"dn"=>"CN=DefaultAccount,CN=Users,DC=mspr,DC=local"));
    array_push($adds,array("samaccountname"=>array("count"=>1,0=>"fabienbayon"),0=>"samaccountname","count"=>1,"dn"=>"CN=mspr,CN=Users,DC=mspr,DC=local"));
    array_push($adds,array("samaccountname"=>array("count"=>1,0=>"maxence.maziere@epsi.fr"),0=>"samaccountname","count"=>1,"dn"=>"CN=Administrateur,CN=Users,DC=mspr,DC=local"));

    var_dump($adds);
    if (isset($_POST['btConnexion']) && $etape == 1){
        $email = $_POST['username']; // on recupere l'email saisie
        $utilisateur = new Utilisateur($db);

        $unUtilisateur = $utilisateur->selectByUsername($email);
        if(isUserInADDS($_POST["username"],$adds) && $unUtilisateur == null){

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
                    \n Pour vous connecter au site, veuillez saisir la cle suivante dans l'application Google Authenticator : " .$userSecret. "  
                    </head>
                    <body>
                    Entrez ensuite le code a 6 chiffres sur l'application lorsque celui-ci est demande par le site.   
                    \n Pour toutes questions supplementaires, veuillez vous referer a la documentation sur l'authentification a double facteurs.    
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

        }elseif(isUserInADDS($_POST["username"],$adds) && $unUtilisateur != null){
            $_SESSION['username'] = $unUtilisateur['username'];
            $etape = 2;
        }else{
            $form['valide'] = false;
            $form['message'] = 'Utilisateur inconnu, veuillez vérifier vos identifiants ou vous rapprocher auprès de votre administrateur';
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
            $etape = 3;
            if($unUtilisateur['ip_address'] != $ip['ip_address']){
                $serveur = $_SERVER['HTTP_HOST'];
                $script = $_SERVER["SCRIPT_NAME"];
                $adressIP = $ip['ip_address'] ;
                $message = "
                <html>
                    <head>
                    Attention, nous venons de detecter une connexion via un nouvel appareil
                    </head>
                    <body>
                    Voici l'addresse IP de celui-ci : " .$adressIP. "
                    Si ce n'est pas vous, veuillez le signaler au plus vite
                    </body>
                </html>
                 ";
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-type: text/html; charset=iso-8859-1';
                mail($email, 'Information Resilience34', $message, implode("\n",$headers));
                $form['valide'] = false;
                $form['message'] = 'l\'adresse ip est différente de celle de votre 1ere connexion';
            }else{
                $form['valide'] = true;
                $form['message'] = 'Authentification réussie';
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
             }else{
                $form['valide'] = true;
                $form['message'] = 'Authentification réussie';
            }
        }else{
            $form['valide'] = false;
            $form['message'] = 'Code incorrect';
        }
         print PHP_EOL;
    }
}else{
    $form['valide'] = false;
    $form['message'] = 'Attention, votre localisation est reconnue hors de la France, votre connexion est donc bloquée';
}
    echo $twig->render('index.html.twig',array('form'=>$form,'etape'=>$etape));
}
