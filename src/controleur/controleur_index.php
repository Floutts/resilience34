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
    $form['valide'] = true;
    $email = "maxence.maziere@epsi.fr";
    $etape = isset($_POST['etape']) ? $_POST['etape'] : 1;
    echo $etape;
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
        $utilisateur = new Utilisateur($db);
        $unUtilisateur = $utilisateur->selectByUsername($_POST['username']);

        if(isUserInADDS($_POST["username"],$adds) && $unUtilisateur == null){

             # Include packages
            require_once(__DIR__ . '/../lib/vendor/autoload.php');

            # Create the 2FA class
            $google2fa = new PragmaRX\Google2FA\Google2FA();

            # Print a user secret for user to enter into their phone. 
            # The application needs to persist this somewhere safely where other users can't get it.
            $userSecret = $google2fa->generateSecretKey();

            $exec = $utilisateur->insert($_POST['username'],$ip,detection_nav(),"maziere.maxence@gmail.com",$userSecret);
            if($exec){
                echo 'utilisateur inséré';
                $_SESSION['username'] = $unUtilisateur['username'];
                $result = smtpmailer($email, 'resilience34.LeChatelet@gmail.com', 'Resilience34', 'Code Google Authenticator', iconv("utf-8","iso-8859-1","Pour vous connecter au site, veuillez saisir la clé suivante dans l'application Google Authenticator : ".$userSecret." \r\nEntrez ensuite le code à 6 chiffres sur l'application lorsque demandé sur le site."));
                if (true !== $result)
                {
                    // erreur -- traiter l'erreur
                    echo $result;
                }
                sendMail($email,"Code Google Authenticator Resilience34","Pour vous connecter au site, veuillez saisir la clé suivante dans l'application Google Authenticator : $userSecret <br> Entrez ensuite le code à 6 chiffres sur l'application lorsque demandé sur le site.");
            }else{
                echo 'erreur';
            }

        }elseif(isUserInADDS($_POST["username"],$adds) && $unUtilisateur != null){
            $_SESSION['username'] = $unUtilisateur['username'];
            $etape = 2;
        }else{
            echo "zetes pas dans l'ADDS sry";
        }
    }
    elseif(isset($_POST['btConnexion']) && $etape == 2){
        $ip = getLocationInfoByIp();
        $utilisateur = new Utilisateur($db);
        $unUtilisateur = $utilisateur->selectByUsername($_SESSION['username']);
        
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
                echo "l'adresse ip est différente de celle de votre 1ere connexion";
                $result = smtpmailer('destinataire@mail.com', 'votreEmail@mail.com', 'votreNom', 'Votre Message', 'Le sujet de votre message');
                if (true !== $result)
                {
                    // erreur -- traiter l'erreur
                    echo $result;
                }
                sendMail($email,"Resilience34 : Adresse Ip différente de d'habitude","Bonjour, Un appareil avec une adresse IP différente vient de se connecter sur votre session, merci de vérifier son authenticité.");
            }elseif($unUtilisateur['browser'] != detection_nav()){
                echo "le navigateur est différent de celui de votre 1ere connexion";
                $result = smtpmailer('destinataire@mail.com', 'votreEmail@mail.com', 'votreNom', 'Votre Message', 'Le sujet de votre message');
if (true !== $result)
{
	// erreur -- traiter l'erreur
	echo $result;
}
                sendMail($email,"Resilience34 : Navigateur différent de d'habitude","Bonjour, Un appareil utilisant un navigateur différent vient de se connecter sur votre session, merci de vérifier son authenticité.");
                $etape = 1;
            }
        }else{
            echo "Authentication FAILED!";
        }
        print PHP_EOL;
    }
    echo $twig->render('index.html.twig',array('form'=>$form,'etape'=>$etape));
}
