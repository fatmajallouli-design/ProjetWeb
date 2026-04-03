<?php
require_once('php/connexionBD.php');
$bdd = ConnexionBD::getInstance();
$sth = $bdd->query('SELECT * FROM message LIMIT 20');
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
echo "Messages count: " . count($rows) . "\n";
foreach ($rows as $r) {
    echo "id_message=".$r['id_message']." id_deal=".$r['id_deal']." sender=".$r['sender_username']." contenu=".$r['contenu']." created_at=".$r['created_at']."\n";
}
?>