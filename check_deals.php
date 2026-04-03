<?php
require_once('php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$sth = $bdd->query('SELECT * FROM deal_request LIMIT 20');
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
echo "Deals count: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "id_deal=".$r['id_deal']." client=".$r['client_username']." vendeur=".$r['vendeur_username']." created_at=".$r['created_at']."\n";
}
?>