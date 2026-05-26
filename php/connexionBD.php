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
            // connexion à MySQL local sur le port 3307
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
            quantite INT NOT NULL DEFAULT 0,
            categorie VARCHAR(30) NOT NULL,
            description TEXT NULL,
            image_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

        try {
            $bdd->exec("ALTER TABLE produit ADD COLUMN quantite INT NOT NULL DEFAULT 0");
        } catch (PDOException $e) {
            // colonne déjà existante
        }

        // dates sur demande si colonne absente dans dump initial
        try {
            $bdd->exec("ALTER TABLE demande ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        } catch (PDOException $e) {
            // colonne déjà existante
        }

        try {
            $bdd->exec("ALTER TABLE demande ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'demande'");
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
        CREATE TABLE IF NOT EXISTS commandes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_demande INT,
            vendeur VARCHAR(255),
            client VARCHAR(255),
            statut VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

        $bdd->exec("
        CREATE TABLE IF NOT EXISTS commande_item (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_commande INT NOT NULL,
            id_produit INT NOT NULL,
            nom_produit VARCHAR(80) NOT NULL,
            prix_unitaire DECIMAL(10,2) NOT NULL,
            quantite INT NOT NULL DEFAULT 1,
            sous_total DECIMAL(10,2) NOT NULL,
            image_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_commande_item_commande (id_commande)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

        try {
            $bdd->exec("ALTER TABLE commandes ADD COLUMN source VARCHAR(20) NOT NULL DEFAULT 'demande'");
        } catch (PDOException $e) {
            // colonne déjà existante
        }

        try {
            $bdd->exec("ALTER TABLE commandes ADD COLUMN total DECIMAL(10,2) NOT NULL DEFAULT 0");
        } catch (PDOException $e) {
            // colonne déjà existante
        }

        $bdd->exec("
        CREATE TABLE IF NOT EXISTS message (
            id_message INT AUTO_INCREMENT PRIMARY KEY,
            id_deal INT NOT NULL,
            sender_username VARCHAR(30) NOT NULL,
            receiver_username VARCHAR(30) NOT NULL,
            contenu TEXT NOT NULL,
            is_read TINYINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

        try {
            $bdd->exec("ALTER TABLE message ADD COLUMN is_read TINYINT NOT NULL DEFAULT 0");
        } catch (PDOException $e) { /* already exists */
        }

        try {
            $bdd->exec("ALTER TABLE deal_request ADD COLUMN client_seen_at TIMESTAMP NULL DEFAULT NULL");
        } catch (PDOException $e) { /* already exists */
        }

        try {
            $bdd->exec("ALTER TABLE deal_request ADD COLUMN vendeur_seen_at TIMESTAMP NULL DEFAULT NULL");
        } catch (PDOException $e) { /* already exists */
        }

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
            // colonne deja existante
        }

        $bdd->exec("
        CREATE TABLE IF NOT EXISTS notification (
            id_notif INT AUTO_INCREMENT PRIMARY KEY,
            recipient_username VARCHAR(30) NOT NULL,
            recipient_role VARCHAR(10) NOT NULL,
            type VARCHAR(30) NOT NULL,
            title VARCHAR(120) NOT NULL,
            body TEXT NULL,
            link VARCHAR(255) NULL,
            actor_username VARCHAR(30) NULL,
            related_id INT NULL,
            is_read TINYINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_notif_recipient (recipient_username, is_read),
            INDEX idx_notif_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        try {
            $bdd->exec("ALTER TABLE message ADD COLUMN thread_key VARCHAR(80) NULL");
        } catch (PDOException $e) { /* exists */ }

        try {
            $bdd->exec("ALTER TABLE message DROP FOREIGN KEY fk_message_deal");
        } catch (PDOException $e) { /* not present */ }

        try {
            $bdd->exec("ALTER TABLE message MODIFY id_deal INT NULL DEFAULT NULL");
        } catch (PDOException $e) { /* ok */ }
    }

    public static function pushNotification(array $opts): int
    {
        $bdd = self::getInstance();
        try {
            $stmt = $bdd->prepare("INSERT INTO notification
                (recipient_username, recipient_role, type, title, body, link, actor_username, related_id)
                VALUES (:r, :ro, :t, :ti, :b, :l, :a, :rid)");
            $stmt->execute([
                'r'   => $opts['recipient_username'],
                'ro'  => $opts['recipient_role'] ?? 'client',
                't'   => $opts['type'] ?? 'generic',
                'ti'  => mb_substr((string)($opts['title'] ?? ''), 0, 120),
                'b'   => $opts['body'] ?? null,
                'l'   => $opts['link'] ?? null,
                'a'   => $opts['actor_username'] ?? null,
                'rid' => $opts['related_id'] ?? null,
            ]);
            return (int)$bdd->lastInsertId();
        } catch (PDOException $e) {
            return 0;
        }
    }
}
