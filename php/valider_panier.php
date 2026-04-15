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

    $commandesParVendeur = [];

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

        $vendeur = (string)($item['vendeur_username'] ?? '');
        if ($vendeur === '') {
            throw new Exception('Vendeur introuvable pour le produit "' . ($item['nom_produit'] ?? 'inconnu') . '".');
        }

        if (!isset($commandesParVendeur[$vendeur])) {
            $commandesParVendeur[$vendeur] = [
                'vendeur' => $vendeur,
                'total' => 0.0,
                'items' => []
            ];
        }

        $sousTotal = ((float)$item['prix']) * ((int)$item['quantite']);
        $commandesParVendeur[$vendeur]['total'] += $sousTotal;

        $commandesParVendeur[$vendeur]['items'][] = [
            'id_produit' => (int)$item['id_produit'],
            'nom_produit' => (string)$item['nom_produit'],
            'prix_unitaire' => (float)$item['prix'],
            'quantite' => (int)$item['quantite'],
            'sous_total' => $sousTotal,
            'image_path' => (string)($item['image_path'] ?? ''),
            'categorie' => (string)($item['categorie'] ?? 'tous')
        ];
    }

    $demandeStmt = $bdd->prepare(
        'INSERT INTO demande (nom_produit, prix, lien_produit, description, categorie, id_photo, username, etat, source)
         VALUES (:nom_produit, :prix, :lien_produit, :description, :categorie, :id_photo, :username, :etat, :source)'
    );
    $commandeStmt = $bdd->prepare(
        'INSERT INTO commandes (id_demande, vendeur, client, statut, source, total)
         VALUES (:id_demande, :vendeur, :client, :statut, :source, :total)'
    );
    $itemStmt = $bdd->prepare(
        'INSERT INTO commande_item (id_commande, id_produit, nom_produit, prix_unitaire, quantite, sous_total, image_path)
         VALUES (:id_commande, :id_produit, :nom_produit, :prix_unitaire, :quantite, :sous_total, :image_path)'
    );

    foreach ($commandesParVendeur as $commandeData) {
        $firstItem = $commandeData['items'][0] ?? null;
        if ($firstItem === null) {
            continue;
        }

        $lines = [];
        foreach ($commandeData['items'] as $orderItem) {
            $lines[] = $orderItem['nom_produit'] . ' x' . $orderItem['quantite'];
        }

        $demandeStmt->execute([
            'nom_produit' => 'Commande panier - ' . $commandeData['vendeur'],
            'prix' => $commandeData['total'],
            'lien_produit' => '',
            'description' => 'Commande creee depuis le panier : ' . implode(', ', $lines),
            'categorie' => $firstItem['categorie'] !== '' ? $firstItem['categorie'] : 'tous',
            'id_photo' => $firstItem['image_path'],
            'username' => $username,
            'etat' => 'en attente',
            'source' => 'panier'
        ]);

        $idDemande = (int)$bdd->lastInsertId();

        $commandeStmt->execute([
            'id_demande' => $idDemande,
            'vendeur' => $commandeData['vendeur'],
            'client' => $username,
            'statut' => 'en cours',
            'source' => 'panier',
            'total' => $commandeData['total']
        ]);

        $idCommande = (int)$bdd->lastInsertId();

        foreach ($commandeData['items'] as $orderItem) {
            $itemStmt->execute([
                'id_commande' => $idCommande,
                'id_produit' => $orderItem['id_produit'],
                'nom_produit' => $orderItem['nom_produit'],
                'prix_unitaire' => $orderItem['prix_unitaire'],
                'quantite' => $orderItem['quantite'],
                'sous_total' => $orderItem['sous_total'],
                'image_path' => $orderItem['image_path']
            ]);
        }
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

