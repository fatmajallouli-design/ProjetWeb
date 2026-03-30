-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 27 mars 2026 à 20:20
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
  `email` varchar(30) NOT NULL,
  `adresse` varchar(50) NOT NULL,
  `num_tel` varchar(20) NOT NULL,
  `idphoto` varchar(255) DEFAULT NULL,
  `password` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `client`
--

INSERT INTO `client` (`username`, `email`, `adresse`, `num_tel`, `idphoto`, `password`) VALUES
('eyaabbes', 'eya.abbes@insat.ucar.tn', 'sfax route menzel cheker km3.5', '29204518', '../files_profil/69c68ebbbf0e5_happy.JPG', 'eyaabbes01'),
('louay', 'benamarlouay6@gmail.com', 'centre urbain nord', '99599926', '../files_profil/69c6bfdc1561f_594837060_2354190965010810_5627566251680704766_n.jpg', 'louaylouay'),
('louaybenamar', 'benamarlouay6@gmail.com', 'centre urbain nord', '99599926', '../files_profil/69c6c04f9fe19_594837060_2354190965010810_5627566251680704766_n.jpg', 'louaylouay');

-- --------------------------------------------------------

--
-- Structure de la table `demande`
--

CREATE TABLE `demande` (
  `id_demande` int(11) NOT NULL,
  `nom_produit` varchar(50) NOT NULL,
  `prix` int(11) NOT NULL,
  `lien_produit` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `categorie` varchar(20) NOT NULL,
  `id_photo` varchar(50) DEFAULT NULL,
  `username` varchar(30) NOT NULL,
  `etat` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demande`
--

INSERT INTO `demande` (`id_demande`, `nom_produit`, `prix`, `lien_produit`, `description`, `categorie`, `id_photo`, `username`, `etat`) VALUES
(4, 'cadre photo', 25, 'http:bfegvy', 'je veux avant le 6/4', 'tous', '../files_demande/69c68f58b2070_happy.JPG', 'eyaabbes', 'en attente');

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

--
-- Déchargement des données de la table `panier`
--

INSERT INTO `panier` (`id_panier`, `username`, `id_produit`, `quantite`, `date_ajout`) VALUES
(1, 'louay', 2, 1, '2026-03-27 18:15:58'),
(3, 'louay', 1, 5, '2026-03-27 18:30:02');

-- --------------------------------------------------------

--
-- Structure de la table `produit`
--

CREATE TABLE `produit` (
  `id_produit` int(11) NOT NULL,
  `nom_produit` varchar(100) NOT NULL,
  `prix` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text NOT NULL,
  `categorie` varchar(50) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`id_produit`, `nom_produit`, `prix`, `description`, `categorie`, `image_path`) VALUES
(1, 'Ecouteurs Sans Fil Pro', 189.00, 'Ecouteurs bluetooth avec reduction de bruit et autonomie longue duree.', 'Audio', '../files_produit/ecouteurs-sans-fil.svg'),
(2, 'Montre Connectee X', 249.00, 'Montre connectee avec suivi sportif, notifications et ecran tactile.', 'Wearable', '../files_produit/montre-connectee.svg');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_demande` int(11) DEFAULT NULL,
  `vendeur` varchar(255) DEFAULT NULL,
  `client` varchar(255) DEFAULT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

-- Aucun enregistrement.

-- --------------------------------------------------------

--
-- Structure de la table `vendeur`
--

CREATE TABLE `vendeur` (
  `username` varchar(30) NOT NULL,
  `email` varchar(30) NOT NULL,
  `adresse` varchar(50) NOT NULL,
  `num_tel` varchar(20) NOT NULL,
  `idphoto` varchar(255) DEFAULT NULL,
  `password` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`username`);

--
-- Index pour la table `demande`
--
ALTER TABLE `demande`
  ADD PRIMARY KEY (`id_demande`),
  ADD KEY `fk_username` (`username`);

--
-- Index pour la table `panier`
--
ALTER TABLE `panier`
  ADD PRIMARY KEY (`id_panier`),
  ADD UNIQUE KEY `unique_user_product` (`username`,`id_produit`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`id_produit`);

--
-- Index pour la table `vendeur`
--
ALTER TABLE `vendeur`
  ADD PRIMARY KEY (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `demande`
--
ALTER TABLE `demande`
  MODIFY `id_demande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `panier`
--
ALTER TABLE `panier`
  MODIFY `id_panier` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `id_produit` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `demande`
--
ALTER TABLE `demande`
  ADD CONSTRAINT `fk_username` FOREIGN KEY (`username`) REFERENCES `client` (`username`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
