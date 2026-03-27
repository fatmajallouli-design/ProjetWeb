<?php 
$nom_produit=$_POST["nom_produit"]?? '';
$prix=$_POST["prix"]?? '';
$categorie=$_POST["categorie"]?? '';
$description=$_POST["description"]?? '';
$lien_produit=$_POST["lien_produit"]?? '';
$newFilePath=null;
 if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $newFilePath = "../files_demande/" . uniqid() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $newFilePath);
    }
require_once("connexionBD.php");
$bdd=ConnexionBD::getInstance();
$red=$bdd->prepare("insert into demande(nom_produit,prix,lien_produit,description,categorie,id_photo) values (:nom_produit,:prix,:lien_produit,:description,:categorie,:id_photo)");
$red->execute(array("nom_produit"=>$nom_produit,"prix"=>$prix,"lien_produit"=>$lien_produit,"description"=>$description,"categorie"=>$categorie,"id_photo"=>$newFilePath));
header("location:page_client.php");
?>