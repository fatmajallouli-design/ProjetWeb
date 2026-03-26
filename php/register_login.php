<?php
session_start();

$username = trim($_POST["username"] ?? "");
$password = $_POST['password'] ?? "";
$confirmPassword = $_POST['confirmPassword'] ?? "";
$tel = trim($_POST['tel'] ?? "");
$email = trim($_POST['email'] ?? "");
$adresse = trim($_POST['adresse'] ?? "");
$role = $_POST["role"] ?? "";
$newFilePath = "";

$_SESSION['old'] = [
    'username' => $username,
    'tel' => $tel,
    'email' => $email,
    'adresse' => $adresse,
    'role' => $role
];

try {
    if (empty($username) || empty($password) || empty($confirmPassword)) {
        throw new Exception("Veuillez remplir tous les champs obligatoires.");
    }

    if ($password !== $confirmPassword) {
        throw new Exception("Les mots de passe ne correspondent pas.");
    }

    if (strlen($password) < 6) {
        throw new Exception("Mot de passe trop court (minimum 6 caractères).");
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] == 4) {
        throw new Exception("Veuillez ajouter une photo.");
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email invalide.");
    }

    if ($role !== "client" && $role !== "vendeur") {
        throw new Exception("Rôle invalide.");
    }

    require_once("connexionBD.php");
    $bdd = ConnexionBD::getInstance();

 
    $req1 = $bdd->prepare("SELECT COUNT(*) FROM client WHERE username = :username");
    $req1->execute(["username" => $username]);
    $existeClient = $req1->fetchColumn();

    $req2 = $bdd->prepare("SELECT COUNT(*) FROM vendeur WHERE username = :username");
    $req2->execute(["username" => $username]);
    $existeVendeur = $req2->fetchColumn();

    if ($existeClient > 0 || $existeVendeur > 0) {
        throw new Exception("Ce nom d'utilisateur existe déjà.");
    }

 
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $newFilePath = "../files/" . uniqid() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $newFilePath);
    }

    
   

    if ($role == "client") {
        $req = $bdd->prepare("INSERT INTO client(username,email,adresse,num_tel,idphoto,password)
                              VALUES (:username,:email,:adresse,:num_tel,:idphoto,:password)");
        $req->execute([
            "username" => $username,
            "email" => $email,
            "adresse" => $adresse,
            "num_tel" => $tel,
            "idphoto" => $newFilePath,
            "password" => $password
        ]);

        unset($_SESSION['old']);
        header("Location: page_client.php");
        exit();
    } else {
        $req = $bdd->prepare("INSERT INTO vendeur(username,email,adresse,num_tel,idphoto,password)
                              VALUES (:username,:email,:adresse,:num_tel,:idphoto,:password)");
        $req->execute([
            "username" => $username,
            "email" => $email,
            "adresse" => $adresse,
            "num_tel" => $tel,
            "idphoto" => $newFilePath,
            "password" => $passwordHash
        ]);

        unset($_SESSION['old']);
        header("Location: page_vendeur.php");
        exit();
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: ../html/login.php");
    exit();
}
?>