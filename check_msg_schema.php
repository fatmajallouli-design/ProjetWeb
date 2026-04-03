<?php
require_once('php/connexionBD.php');
$db = ConnexionBD::getInstance();
$rows = $db->query('DESCRIBE message')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo implode(' | ', $r) . "\n";
}
?>