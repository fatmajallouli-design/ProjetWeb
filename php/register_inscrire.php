<?php
$username=$_POST["username"];
$password =  $_POST['password'];
$role=$_POST['role'];
require_once("connexionBD.php");
$bdd=ConnexionBD::getInstance();

if ($role== 'client') 
{   
    $req=$bdd->prepare("select * from client where username=:username and password=:password;");
    $req->execute(array("username"=>$username,"password"=>$password));
    $res=$req->fetch(PDO::FETCH_OBJ);
    if ($res)
        {
            header("location:page_client.php");
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