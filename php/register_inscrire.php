<?php
$username=$_POST["username"];
$password =  $_POST['password'];
$role=$_POST['role'];
require_once("connexionBD.php");
$bdd=ConnexionBD::getInstance();
session_start();

$_SESSION['user'] = [
    'username' => $username,
    'role' => $role
];

if ($role== 'client') 
{   
    $req=$bdd->prepare("select * from client where username=:username and password=:password;");
    $req->execute(array("username"=>$username,"password"=>$password));
    $res=$req->fetch(PDO::FETCH_OBJ);
    if ($res)
        {
            header("location:./html/client-interface.php");
            exit();
        }
    else
        {
           header("location:../html/inscrire.html");
            exit();
        }
}
else
    {

    }



?>