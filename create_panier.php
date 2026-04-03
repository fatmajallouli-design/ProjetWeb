<?php
require_once('php/connexionBD.php');
$bdd = ConnexionBD::getInstance();

$sql = "CREATE TABLE `panier` (
  `id_panier` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

try {
    $bdd->exec($sql);
    echo "Table panier created successfully\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}

$sql2 = "ALTER TABLE `panier` ADD PRIMARY KEY (`id_panier`);";
try {
    $bdd->exec($sql2);
    echo "Primary key added\n";
} catch (PDOException $e) {
    echo "Error adding primary key: " . $e->getMessage() . "\n";
}

$sql3 = "ALTER TABLE `panier` MODIFY `id_panier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;";
try {
    $bdd->exec($sql3);
    echo "Auto increment set\n";
} catch (PDOException $e) {
    echo "Error setting auto increment: " . $e->getMessage() . "\n";
}
?>