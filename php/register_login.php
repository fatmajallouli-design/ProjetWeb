 <?php 
    $username=$_POST["username"];
    
    $password =  $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $tel= $_POST['tel'];
    $email= $_POST['email'];
    $adresse= $_POST['adresse'];
    $role=$_POST["role"];
     $newFilePath="";

    if(isset($_FILES['image'])&& $_FILES['image']['error'] == 0) {
        $newFilePath = "../files/".uniqid().$_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $newFilePath);
    }
    require_once("connexionBD.php");
    $bdd=ConnexionBD::getInstance();



if ($role== "client")
    {   
         $req=$bdd->prepare("insert into client(username,email,adresse,num_tel,idphoto,password) values (:username,:email,:adresse,:num_tel,:idphoto,:password);");
        $req->execute(array("username"=>$username,"email"=>$email,"password"=>$password,"adresse"=>$adresse,"num_tel"=>$tel,"idphoto"=>$newFilePath));
        header("location:page_client.php");
        exit();}
else{
    $req=$bdd->prepare("insert into vendeur(username,email,adresse,num_tel,idphoto,password) values (:username,:email,:adresse,:num_tel,:idphoto,:password);");
    $req->execute(array("username"=>$username,"email"=>$email,"password"=>$password,"adresse"=>$adresse,"num_tel"=>$tel,"idphoto"=>$newFilePath));
    header("location:page_vendeur.php");
    exit();
}




?>  