-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 31 mars 2026 à 00:59
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `my_site`
--

-- --------------------------------------------------------

--
-- Structure de la table `client`
--

CREATE TABLE `client` (
  `username` varchar(30) NOT NULL,
  `email` varchar(100) NOT NULL,
  `adresse` varchar(100) NOT NULL,
  `num_tel` varchar(20) NOT NULL,
  `idphoto` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `client`
--

INSERT INTO `client` (`username`, `email`, `adresse`, `num_tel`, `idphoto`, `password`, `created_at`) VALUES
('amira', 'amira@email.com', 'Nabeul', '55667788', '../files_profil/amira.jpg', 'amira123', '2026-03-30 20:36:09'),
('eyaabbes', 'eya.abbes@insat.ucar.tn', 'Sfax route menzel cheker km3.5', '29204518', '../files_profil/69cae197863a0_653545902_955745413598440_7237380571186557598_n.jpg', 'eyaabbes01', '2026-03-30 20:36:09'),
('skander', 'skander@email.com', 'Tunis centre', '22334455', '../files_profil/skander.jpg', 'skander123', '2026-03-30 20:36:09');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `id_demande` int(11) DEFAULT NULL,
  `vendeur` varchar(255) DEFAULT NULL,
  `client` varchar(255) DEFAULT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `id_demande`, `vendeur`, `client`, `statut`, `created_at`) VALUES
(1, 9, 'farah zayeni', 'eyaabbes', 'en cours', '2026-03-30 22:26:02'),
(2, 10, 'farah zayeni', 'eyaabbes', 'en cours', '2026-03-30 22:26:55'),
(3, 11, 'farah zayeni', 'eyaabbes', 'en cours', '2026-03-30 22:35:51'),
(4, 12, 'farah zayeni', 'eyaabbes', 'en cours', '2026-03-30 22:39:44'),
(5, 4, 'mohamedabbes', 'eyaabbes', 'termine', '2026-03-30 22:46:35'),
(6, 13, 'farah zayeni', 'eyaabbes', 'en cours', '2026-03-30 23:35:11'),
(7, 4, 'farah zayeni', 'eyaabbes', 'en cours', '2026-03-30 23:36:31');

-- --------------------------------------------------------

--
-- Structure de la table `deal_request`
--

CREATE TABLE `deal_request` (
  `id_deal` int(11) NOT NULL,
  `id_demande` int(11) NOT NULL,
  `client_username` varchar(30) NOT NULL,
  `vendeur_username` varchar(30) NOT NULL,
  `prix_propose` decimal(10,2) NOT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'en attente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `deal_request`
--

INSERT INTO `deal_request` (`id_deal`, `id_demande`, `client_username`, `vendeur_username`, `prix_propose`, `message`, `status`, `created_at`) VALUES
(1, 1, 'eyaabbes', 'phone_store', 22.00, 'Bonjour, je peux vous livrer avant le 06/04.', 'en attente', '2026-03-30 20:36:10'),
(2, 2, 'skander', 'phone_store', 1750.00, 'iPhone 13 disponible, très bon état.', 'accepté', '2026-03-30 20:36:10'),
(3, 3, 'amira', 'tech_shop', 430.00, 'Je peux vous proposer un modèle original.', 'en attente', '2026-03-30 20:36:10'),
(4, 4, 'eyaabbes', 'farah zayeni', 1700.00, 'avec les frais de livraison,il sera a 1700dt et il sera dispo bientot', 'accepte', '2026-03-30 22:42:52'),
(5, 4, 'eyaabbes', 'mohamedabbes', 1650.00, 'il sera dispo debut mai si ca te va', 'accepte', '2026-03-30 22:44:12');

-- --------------------------------------------------------

--
-- Structure de la table `demande`
--

CREATE TABLE `demande` (
  `id_demande` int(11) NOT NULL,
  `nom_produit` varchar(80) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `lien_produit` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `categorie` varchar(30) NOT NULL,
  `id_photo` varchar(255) DEFAULT NULL,
  `username` varchar(30) NOT NULL,
  `etat` varchar(20) NOT NULL DEFAULT 'en attente',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demande`
--

INSERT INTO `demande` (`id_demande`, `nom_produit`, `prix`, `lien_produit`, `description`, `categorie`, `id_photo`, `username`, `etat`, `created_at`) VALUES
(1, 'Cadre photo', 25.00, 'http://exemple.com/cadre-photo', 'Je veux ce produit avant le 06/04', 'tous', '../files_demande/cadre.jpg', 'eyaabbes', 'en attente', '2026-03-30 20:36:10'),
(2, 'iPhone 13', 1800.00, 'http://exemple.com/iphone13', 'Je cherche un iPhone 13 en bon état', 'smartphone', '../files_demande/iphone13.jpg', 'skander', 'en attente', '2026-03-30 20:36:10'),
(3, 'AirPods Pro', 450.00, 'http://exemple.com/airpods-pro', 'Je veux des écouteurs originaux', 'accessoire', '../files_demande/airpods.jpg', 'amira', 'en attente', '2026-03-30 20:36:10'),
(4, 'ceinture DG', 1430.00, 'https://www.farfetch.com/tn/shopping/women/dolce-gabbana-ceinture-en-cuir-item-32473932.aspx', 'je veux cette ceinture mais je ne peux pas payer tout le montant directement,je vais payer avance 500 puis 1000 et merci', 'femme', '../files_produits/69cae23d09787_Capture d\'écran 2026-03-30 215047.png', 'eyaabbes', 'recu', '2026-03-30 21:51:09'),
(5, 'gloss fenty beauty', 110.00, 'https://www.sephora.fr/p/gloss-bomb-heat---enlumineur-a-levres-universel-et-repulplant-P10017314.html', 'je veux ce produit le plus tot possible,avec la ref fussy svp', 'beaute', '../files_produits/69cae2a468613_Capture d\'écran 2026-03-30 215238.png', 'eyaabbes', 'en attente', '2026-03-30 21:52:52'),
(6, 'highlighter fenty beauty', 105.00, 'https://www.sephora.fr/p/mini-killawatt-freestyle-highlighter---maquillage--teint--highlighter-792355.html', 'je veux cette ref et si tu peux me livrer das autres articles on parle en messages prives', 'tous', '../files_produits/69cae31b775da_Capture d\'écran 2026-03-30 215353.png', 'eyaabbes', 'en attente', '2026-03-30 21:54:51'),
(13, 'dior backstage', 265.00, '', 'limited edition!!\nQuantité: 1', 'beaute', '../files_produit/69cae563bb015_Capture d\'écran 2026-03-30 153907.png', 'eyaabbes', 'en attente', '2026-03-30 23:35:11');

-- --------------------------------------------------------

--
-- Structure de la table `message`
--

CREATE TABLE `message` (
  `id_message` int(11) NOT NULL,
  `id_deal` int(11) NOT NULL,
  `sender_username` varchar(30) NOT NULL,
  `receiver_username` varchar(30) NOT NULL,
  `contenu` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `message`
--

INSERT INTO `message` (`id_message`, `id_deal`, `sender_username`, `receiver_username`, `contenu`, `created_at`) VALUES
(1, 1, 'phone_store', 'eyaabbes', 'Bonjour, votre demande est disponible.', '2026-03-30 20:36:10'),
(2, 1, 'eyaabbes', 'phone_store', 'Merci, combien de jours pour la livraison ?', '2026-03-30 20:36:10'),
(3, 2, 'skander', 'phone_store', 'Parfait, je confirme la commande.', '2026-03-30 20:36:10'),
(4, 4, 'farah zayeni', 'eyaabbes', 'bonjour eya je peux l apporter pour toi meme si tu veux autre chose je peux mais ne depasse pas 2000 dt svp', '2026-03-30 22:43:22'),
(5, 5, 'mohamedabbes', 'eyaabbes', 'bonsoir eya,si je vais venir avant je vais t informer', '2026-03-30 22:44:35');

-- --------------------------------------------------------

--
-- Structure de la table `panier`
--

CREATE TABLE `panier` (
  `id_panier` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `id_produit` int(11) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `date_ajout` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id_produit` int(11) NOT NULL,
  `vendeur_username` varchar(30) NOT NULL,
  `nom_produit` varchar(80) NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 0,
  `categorie` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id_produit`, `vendeur_username`, `nom_produit`, `prix`, `quantite`, `categorie`, `description`, `image_path`, `created_at`) VALUES
(5, 'mohamedabbes', 'parfum ysl', 520.00, 5, 'femme', 'cest un parfum nouveaute de ysl a ne pas rater et sera dispo a tunis avant l ete quantite limitee!!', '../files_produit/69cadfddb4de4_Capture d\'écran 2026-03-30 214047.png', '2026-03-30 21:41:01'),
(6, 'mohamedabbes', 'ceinture coach', 500.00, 1, 'homme', 'ceinture une seule piece dispo pemier arrive permier servi', '../files_produit/69cae03b202b4_Capture d\'écran 2026-03-30 214148.png', '2026-03-30 21:42:35'),
(7, 'mohamedabbes', 'tissout', 1220.00, 1, 'homme', 'a ne pas rater elle est deja disponible en Tunis', '../files_produit/69cae0dc190a7_Capture d\'écran 2026-03-30 214423.png', '2026-03-30 21:45:16'),
(8, 'mohamedabbes', 'ceinture coach femme', 450.00, 3, 'femme', 'tres joli article ,cadeau pour fetes des meres', '../files_produit/69cae152c835a_Capture d\'écran 2026-03-30 214600.png', '2026-03-30 21:47:14'),
(9, 'farah zayeni', 'medicube mask collagene', 95.00, 15, 'femme', 'mask korean tendance', '../files_produit/69cae424bbea1_Capture d\'écran 2026-03-30 215901.png', '2026-03-30 21:59:16'),
(10, 'farah zayeni', 'serum collagene', 105.00, 10, 'femme', 'a ne pas rater deja dispo en tunisie!!', '../files_produit/69cae462e1786_Capture d\'écran 2026-03-30 215935.png', '2026-03-30 22:00:18'),
(11, 'farah zayeni', 'parfum prada paradox', 520.00, 3, 'homme', 'quantite limitee!!!Deja dispo en Tunisie', '../files_produit/69cae497d4b56_Capture d\'écran 2026-03-30 181429.png', '2026-03-30 22:01:11'),
(12, 'farah zayeni', 'chanel Eau De Toilette Vaporisateur', 595.00, 1, 'beaute', 'dispo max le 29 avril just une seule piece', '../files_produit/69cae4e0d7782_Capture d\'écran 2026-03-30 220125.png', '2026-03-30 22:02:24'),
(13, 'farah zayeni', 'ysl fard a popiere', 235.00, 8, 'femme', 'des couleurs tres utiles', '../files_produit/69cae5315876d_Capture d\'écran 2026-03-30 220244.png', '2026-03-30 22:03:45'),
(14, 'farah zayeni', 'dior backstage', 265.00, 2, 'beaute', 'limited edition!!', '../files_produit/69cae563bb015_Capture d\'écran 2026-03-30 153907.png', '2026-03-30 22:04:35'),
(15, 'aidakeskes', 'dyson airwrap', 2100.00, 1, 'femme', 'a ne pas rater!!une seule piece dispo', '../files_produit/69cae64862dfa_Capture d\'écran 2026-03-30 220731.png', '2026-03-30 22:08:24');

-- --------------------------------------------------------

--
-- Structure de la table `review`
--

CREATE TABLE `review` (
  `id_review` int(11) NOT NULL,
  `id_deal` int(11) NOT NULL,
  `client_username` varchar(30) NOT NULL,
  `vendeur_username` varchar(30) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

--
-- Déchargement des données de la table `review`
--

INSERT INTO `review` (`id_review`, `id_deal`, `client_username`, `vendeur_username`, `rating`, `commentaire`, `created_at`) VALUES
(1, 2, 'skander', 'phone_store', 5, 'Très bon vendeur, livraison rapide.', '2026-03-30 20:36:10');

-- --------------------------------------------------------

--
-- Structure de la table `vendeur`
--

CREATE TABLE `vendeur` (
  `username` varchar(30) NOT NULL,
  `email` varchar(100) NOT NULL,
  `adresse` varchar(100) NOT NULL,
  `num_tel` varchar(20) NOT NULL,
  `idphoto` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vendeur`
--

INSERT INTO `vendeur` (`username`, `email`, `adresse`, `num_tel`, `idphoto`, `password`, `created_at`) VALUES
('aidakeskes', 'aida@gmail.com', 'Sfax route menzel cheker km3.5', '98532019', '../files_profil/69cae609bd6f1_Capture d\'écran 2026-03-30 220629.png', 'aidakeskes01', '2026-03-30 22:07:21'),
('farah zayeni', 'farah@gmail.com', 'sfax centre ville', '54256569', '../files_profil/69cae3b6acfc9_Capture d\'écran 2026-03-30 215712.png', 'farah01', '2026-03-30 21:57:26'),
('mohamedabbes', 'mohamed@gmail.com', 'tunis la marsa', '21441277', '../files_profil/69cadf89b325c_Capture d\'écran 2026-03-30 213838.png', 'mohamed01', '2026-03-30 21:39:37'),
('phone_store', 'store@email.com', 'Lafayette Tunis', '94367714', '../files_profil/store.jpg', 'store123', '2026-03-30 20:36:10'),
('tech_shop', 'tech@email.com', 'Sousse', '99887766', '../files_profil/tech.jpg', 'tech123', '2026-03-30 20:36:10');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`username`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `deal_request`
--
ALTER TABLE `deal_request`
  ADD PRIMARY KEY (`id_deal`),
  ADD KEY `fk_deal_demande` (`id_demande`),
  ADD KEY `fk_deal_client` (`client_username`),
  ADD KEY `fk_deal_vendeur` (`vendeur_username`);

--
-- Index pour la table `demande`
--
ALTER TABLE `demande`
  ADD PRIMARY KEY (`id_demande`),
  ADD KEY `fk_demande_client` (`username`);

--
-- Index pour la table `message`
--
ALTER TABLE `message`
  ADD PRIMARY KEY (`id_message`),
  ADD KEY `fk_message_deal` (`id_deal`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id_panier`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id_produit`),
  ADD KEY `fk_produit_vendeur` (`vendeur_username`);

--
-- Index pour la table `review`
--
ALTER TABLE `review`
  ADD PRIMARY KEY (`id_review`),
  ADD KEY `fk_review_deal` (`id_deal`),
  ADD KEY `fk_review_client` (`client_username`),
  ADD KEY `fk_review_vendeur` (`vendeur_username`);

--
-- Index pour la table `vendeur`
--
ALTER TABLE `vendeur`
  ADD PRIMARY KEY (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `deal_request`
--
ALTER TABLE `deal_request`
  MODIFY `id_deal` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `demande`
--
ALTER TABLE `demande`
  MODIFY `id_demande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `message`
--
ALTER TABLE `message`
  MODIFY `id_message` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `id_panier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `review`
--
ALTER TABLE `review`
  MODIFY `id_review` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `deal_request`
--
ALTER TABLE `deal_request`
  ADD CONSTRAINT `fk_deal_client` FOREIGN KEY (`client_username`) REFERENCES `client` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_deal_demande` FOREIGN KEY (`id_demande`) REFERENCES `demande` (`id_demande`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_deal_vendeur` FOREIGN KEY (`vendeur_username`) REFERENCES `vendeur` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `demande`
--
ALTER TABLE `demande`
  ADD CONSTRAINT `fk_demande_client` FOREIGN KEY (`username`) REFERENCES `client` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `message`
--
ALTER TABLE `message`
  ADD CONSTRAINT `fk_message_deal` FOREIGN KEY (`id_deal`) REFERENCES `deal_request` (`id_deal`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `fk_produit_vendeur` FOREIGN KEY (`vendeur_username`) REFERENCES `vendeur` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `review`
--
ALTER TABLE `review`
  ADD CONSTRAINT `fk_review_client` FOREIGN KEY (`client_username`) REFERENCES `client` (`username`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_deal` FOREIGN KEY (`id_deal`) REFERENCES `deal_request` (`id_deal`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_review_vendeur` FOREIGN KEY (`vendeur_username`) REFERENCES `vendeur` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
