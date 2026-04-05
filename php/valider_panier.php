<?php
session_start();

if (empty($_SESSION['user']['username'])) {
    header('Location: /login.php');
    exit();
}

require_once(__DIR__ . "/connexionBD.php");
$bdd = ConnexionBD::getInstance();
ConnexionBD::ensureWorkflowTables();

$username = $_SESSION['user']['username'];

$stmt = $bdd->prepare(
    'SELECT p.id_panier, p.quantite, pr.id_produit, pr.nom_produit, pr.prix, pr.description, pr.categorie, pr.image_path, pr.vendeur_username, pr.quantite AS stock
     FROM panier p
     INNER JOIN produit pr ON pr.id_produit = p.id_produit
     WHERE p.username = :username'
);
$stmt->execute(['username' => $username]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    $_SESSION['panier_error'] = 'Votre panier est vide.';
    header('Location: ../html/panier.php');
    exit();
}

try {
    $bdd->beginTransaction();

    foreach ($items as $item) {
        if ((int)$item['stock'] < (int)$item['quantite']) {
            throw new Exception('Le produit "' . ($item['nom_produit'] ?? 'inconnu') . '" n\'est plus disponible en quantite suffisante.');
        }

        $stockUpdate = $bdd->prepare('UPDATE produit SET quantite = quantite - :quantite WHERE id_produit = :id_produit AND quantite >= :quantite');
        $stockUpdate->execute([
            'quantite' => (int)$item['quantite'],
            'id_produit' => $item['id_produit']
        ]);

        if ($stockUpdate->rowCount() === 0) {
            throw new Exception('Stock insuffisant pour "' . ($item['nom_produit'] ?? 'inconnu') . '".');
        }

        $description = trim(($item['description'] ?? '') . "\nQuantité: " . (int)$item['quantite']);
        $prix = ((float)$item['prix']) * ((int)$item['quantite']);

        $demandeStmt = $bdd->prepare(
            'INSERT INTO demande (nom_produit, prix, lien_produit, description, categorie, id_photo, username, etat)
             VALUES (:nom_produit, :prix, :lien_produit, :description, :categorie, :id_photo, :username, :etat)'
        );
        $demandeStmt->execute([
            'nom_produit' => $item['nom_produit'],
            'prix' => $prix,
            'lien_produit' => '',
            'description' => $description,
            'categorie' => $item['categorie'] ?? '',
            'id_photo' => $item['image_path'] ?? '',
            'username' => $username,
            'etat' => 'en attente'
        ]);

        $idDemande = $bdd->lastInsertId();

        $commandeStmt = $bdd->prepare(
            'INSERT INTO commandes (id_demande, vendeur, client, statut)
             VALUES (:id_demande, :vendeur, :client, :statut)'
        );
        $commandeStmt->execute([
            'id_demande' => $idDemande,
            'vendeur' => $item['vendeur_username'],
            'client' => $username,
            'statut' => 'en cours'
        ]);
    }

    $delStmt = $bdd->prepare('DELETE FROM panier WHERE username = :username');
    $delStmt->execute(['username' => $username]);

    $bdd->commit();
    $_SESSION['panier_success'] = 'Panier validé et envoyé au vendeur.';
} catch (Exception $e) {
    $bdd->rollBack();
    $_SESSION['panier_error'] = 'Erreur lors de la validation du panier : ' . $e->getMessage();
}

header('Location: ../html/panier.php');
exit();


