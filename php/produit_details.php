<?php
session_start();

require_once(__DIR__ . '/connexionBD.php');
$bdd = ConnexionBD::getInstance();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$returnTo = trim($_GET['return_to'] ?? '/index.php');

if ($returnTo === '' || preg_match('/^https?:/i', $returnTo)) {
    $returnTo = '/index.php';
}

if ($id <= 0) {
    die("Produit introuvable");
}

$req = $bdd->prepare("SELECT * FROM produit WHERE id_produit = :id");
$req->execute(["id" => $id]);
$produit = $req->fetch(PDO::FETCH_ASSOC);

if (!$produit) {
    die("Produit introuvable");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Details produit</title>
<link rel="stylesheet" href="../css/details.css">
</head>
<body>


<div class="product-page">

  <div class="product-left">
    <img src="<?= htmlspecialchars($produit['image_path']) ?>" alt="">
  </div>

  <div class="product-right">

    <span class="category"><?= htmlspecialchars($produit['categorie']) ?></span>

    <h1><?= htmlspecialchars($produit['nom_produit']) ?></h1>

    <p class="price"><strong>prix: </strong><?= htmlspecialchars($produit['prix']) ?> DT</p>


    

    <!-- BOUTON -->
    <form action="../html/login.php" method="post">
      <input type="hidden" name="id_produit" value="<?= $produit['id_produit'] ?>">

      <button class="btn-cart"
        <?= ((int)$produit['quantite'] <= 0) ? 'disabled' : '' ?>>
        <?= ((int)$produit['quantite'] <= 0) ? 'Indisponible' : 'Ajouter au panier' ?>
      </button>
    </form>
    <p class="toggle-desc" onclick="toggleDescription()">Voir la description</p>
    <div class="description" id="descriptionBox">
      <?= htmlspecialchars($produit['description']) ?>
    <div class="detail-actions">
        <form action="/php/add_to_panier.php" method="post" id="detailAddToCartForm">
            <input type="hidden" name="id_produit" value="<?= (int) $produit['id_produit'] ?>">
            <input type="hidden" name="redirect_to" value="/details.php?id=<?= (int) $produit['id_produit'] ?>&return_to=<?= urlencode($returnTo) ?>">
            <button type="submit" <?= ((int)$produit['quantite'] <= 0) ? 'disabled' : '' ?>><?= ((int)$produit['quantite'] <= 0) ? 'Indisponible' : 'Ajouter au panier' ?></button>
        </form>
        <a class="back-link" href="<?= htmlspecialchars($returnTo) ?>">Retour</a>
    </div>

  </div>

</div>

<script>
const cartToast = document.getElementById('cartToast');
const detailAddToCartForm = document.getElementById('detailAddToCartForm');

function showCartToast(message) {
  if (!cartToast) return;
  cartToast.textContent = message;
  cartToast.classList.add('visible');
  window.clearTimeout(showCartToast.timeoutId);
  showCartToast.timeoutId = window.setTimeout(function () {
    cartToast.classList.remove('visible');
  }, 2200);
}

if (detailAddToCartForm) {
  detailAddToCartForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    try {
      const response = await fetch(detailAddToCartForm.action, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        body: new FormData(detailAddToCartForm)
      });

      const data = await response.json();

      if (data.redirect && !data.success) {
        window.location.href = data.redirect;
        return;
      }

      showCartToast(data.message || 'Produit ajoute dans le panier.');
    } catch (error) {
      showCartToast('Erreur lors de l ajout au panier.');
    }
  });

}

function toggleDescription() {
  let box = document.getElementById("descriptionBox");

  if (box.style.display === "block") {
    box.style.display = "none";
  } else {
    box.style.display = "block";
  }
}

</script>
</body>
</html>
