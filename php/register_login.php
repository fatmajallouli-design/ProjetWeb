 <?php 
    $name=$_POST["name"];
    $password =  $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $tel= $_POST['tel'];
    $email= $_POST['email'];
    $adresse= $_POST['adresse'];
    $role=$_POST["role"];
    

try{
    if (empty($name) || empty($password) || empty($confirmPassword)) {
        throw new Exception("Veuillez remplir tous les champs obligatoires");
    }

    if ($password !== $confirmPassword) {
        throw new Exception("Les mots de passe ne correspondent pas");
    }

    if (strlen($password) < 6) {
        throw new Exception("Mot de passe trop court (min 6 caractères)");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email invalide");
    }

    if ($role !== "client" && $role !== "vendeur") {
        throw new Exception("Rôle invalide");
    }
    if(isset($_FILES['image'])) {
        $newFilePath = "files/".uniqid().$_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $newFilePath);
    }



if ($role== "client")
    {header("location:page_client.php");
exit();}
else{
    header("location:page_vendeur.php");
    exit();
}
}catch(Exception $e){
    echo $e->getMessage();
    header("location:../html/login.html");
    exit();
}
?>  