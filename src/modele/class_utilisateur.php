<?php
class Utilisateur
{
 
    private $db;
    private $insert;
    private $selectByUsername;
    private $updateAddress;
 
 
    public function __construct($db)
    {
        $this->db = $db;
        $this->insert = $db->prepare("INSERT INTO utilisateur(username, ip_address, browser, email, googleKey, tryCount) VALUES (:username, :ip_address, :browser, :email,:googleKey, '0')");
        $this->selectByUsername = $db->prepare("SELECT * FROM utilisateur WHERE username = :username ");
        //$this->updateAddress= $db->prepare("UPDATE resilience34 set address=:address where username=:username ");
    }
 
    public function insert($username, $ip_address,$browser,$email,$googleKey)
    {
        $r = true;
        $this->insert->execute(array(':username' => $username, ':ip_address' => $ip_address, ':browser' => $browser, ':email' => $email, ':googleKey' => $googleKey));
        if ($this->insert->errorCode() != 0) {
            print_r($this->insert->errorInfo());
            $r = false;
        }
        return $r;
    }
 
    public function selectByUsername($username){
        $this->selectByUsername->execute(array(':username' => $username)) ;
        if ($this->selectByUsername->errorCode() !=0){
            print_r($this->selectByUsername->errorInfo()) ;
        }
        return $this->selectByUsername->fetch() ;
    }
 
    // public function updateAddress($username,$address){
    //     $r = true;
    //     $this->updateAddress->execute(array(':username' => $username, ':address' => $address));
    //     if ($this->updateAddress->errorCode()!=0){
    //         print_r($this->updateAddress->errorInfo());
    //         $r=false;
    //     }
    //     return $r;
    // }
}
