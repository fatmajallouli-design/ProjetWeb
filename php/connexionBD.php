<?php
class ConnexionBD
{
private static $_dbname = "my_site";
private static $_user = "root";
private static $_pwd = ""; // votre mot de passe MySQL
private static $_host = "localhost"; 
private static $_bdd = null;

private function __construct()
{
    try {
        // connexion à MySQL local sur le port 3306
        $dsn = "mysql:host=" . self::$_host . ";port=3306;dbname=" . self::$_dbname . ";charset=utf8";
        $options = [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8',
            PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
        ];
        self::$_bdd = new PDO($dsn, self::$_user, self::$_pwd, $options);

        // créer les tables de workflow si besoin
        self::ensureWorkflowTables();

    } catch (PDOException $e) {
        die('Erreur DB : ' . $e->getMessage());
    }
}

public static function getInstance()
{
    if (!self::$_bdd) {
        new ConnexionBD();
    }
    return self::$_bdd;
}

public static function ensureWorkflowTables()
{
    $bdd = self::getInstance();

    // table produit pour les posts vendeurs
    $bdd->exec("
        CREATE TABLE IF NOT EXISTS produit (
            id_produit INT AUTO_INCREMENT PRIMARY KEY,
            vendeur_username VARCHAR(30) NOT NULL,
            nom_produit VARCHAR(80) NOT NULL,
            prix DECIMAL(10,2) NOT NULL,
            categorie VARCHAR(30) NOT NULL,
            description TEXT NULL,
            image_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // dates sur demande si colonne absente dans dump initial
    try {
        $bdd->exec("ALTER TABLE demande ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    } catch (PDOException $e) {
        // colonne déjà existante
    }

    // tables workflow
    $bdd->exec("
        CREATE TABLE IF NOT EXISTS deal_request (
            id_deal INT AUTO_INCREMENT PRIMARY KEY,
            id_demande INT NOT NULL,
            client_username VARCHAR(30) NOT NULL,
            vendeur_username VARCHAR(30) NOT NULL,
            prix_propose DECIMAL(10,2) NOT NULL,
            message TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'en attente',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $bdd->exec("
        CREATE TABLE IF NOT EXISTS message (
            id_message INT AUTO_INCREMENT PRIMARY KEY,
            id_deal INT NOT NULL,
            sender_username VARCHAR(30) NOT NULL,
            receiver_username VARCHAR(30) NOT NULL,
            contenu TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $bdd->exec("
        CREATE TABLE IF NOT EXISTS review (
            id_review INT AUTO_INCREMENT PRIMARY KEY,
            id_deal INT NOT NULL,
            client_username VARCHAR(30) NOT NULL,
            vendeur_username VARCHAR(30) NOT NULL,
            rating TINYINT NOT NULL,
            commentaire TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    try {
        $bdd->exec("ALTER TABLE deal_request ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    } catch (PDOException $e) {
        // colonne déjà existante
    }
}
}