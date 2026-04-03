<?php
require_once('php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$tables = $bdd->query("SHOW TABLES");
while ($row = $tables->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "\n";
}
?>