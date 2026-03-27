-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : ven. 27 mars 2026 à 15:28
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

USE my_site;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =========================
-- 1) Tables principales
-- =========================

DROP TABLE IF EXISTS review;
DROP TABLE IF EXISTS message;
DROP TABLE IF EXISTS deal_request;
DROP TABLE IF EXISTS produit;
DROP TABLE IF EXISTS demande;
DROP TABLE IF EXISTS vendeur;
DROP TABLE IF EXISTS client;

CREATE TABLE client (
  username VARCHAR(30) NOT NULL,
  email VARCHAR(100) NOT NULL,
  adresse VARCHAR(100) NOT NULL,
  num_tel VARCHAR(20) NOT NULL,
  idphoto VARCHAR(255) DEFAULT NULL,
  password VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE vendeur (
  username VARCHAR(30) NOT NULL,
  email VARCHAR(100) NOT NULL,
  adresse VARCHAR(100) NOT NULL,
  num_tel VARCHAR(20) NOT NULL,
  idphoto VARCHAR(255) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE demande (
  id_demande INT NOT NULL AUTO_INCREMENT,
  nom_produit VARCHAR(80) NOT NULL,
  prix DECIMAL(10,2) NOT NULL,
  lien_produit VARCHAR(255) NOT NULL,
  description TEXT NOT NULL,
  categorie VARCHAR(30) NOT NULL,
  id_photo VARCHAR(255) DEFAULT NULL,
  username VARCHAR(30) NOT NULL,
  etat VARCHAR(20) NOT NULL DEFAULT 'en attente',
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_demande),
  KEY fk_demande_client (username),
  CONSTRAINT fk_demande_client
    FOREIGN KEY (username) REFERENCES client(username)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- 2) Produits vendeurs
-- =========================

CREATE TABLE produit (
  id_produit INT NOT NULL AUTO_INCREMENT,
  vendeur_username VARCHAR(30) NOT NULL,
  nom_produit VARCHAR(80) NOT NULL,
  prix DECIMAL(10,2) NOT NULL,
  categorie VARCHAR(30) NOT NULL,
  description TEXT DEFAULT NULL,
  image_path VARCHAR(255) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_produit),
  KEY fk_produit_vendeur (vendeur_username),
  CONSTRAINT fk_produit_vendeur
    FOREIGN KEY (vendeur_username) REFERENCES vendeur(username)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- 3) Offres vendeurs sur demandes clients
-- =========================

CREATE TABLE deal_request (
  id_deal INT NOT NULL AUTO_INCREMENT,
  id_demande INT NOT NULL,
  client_username VARCHAR(30) NOT NULL,
  vendeur_username VARCHAR(30) NOT NULL,
  prix_propose DECIMAL(10,2) NOT NULL,
  message TEXT DEFAULT NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'en attente',
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_deal),
  KEY fk_deal_demande (id_demande),
  KEY fk_deal_client (client_username),
  KEY fk_deal_vendeur (vendeur_username),
  CONSTRAINT fk_deal_demande
    FOREIGN KEY (id_demande) REFERENCES demande(id_demande)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_deal_client
    FOREIGN KEY (client_username) REFERENCES client(username)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_deal_vendeur
    FOREIGN KEY (vendeur_username) REFERENCES vendeur(username)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- 4) Messages chat
-- =========================

CREATE TABLE message (
  id_message INT NOT NULL AUTO_INCREMENT,
  id_deal INT NOT NULL,
  sender_username VARCHAR(30) NOT NULL,
  receiver_username VARCHAR(30) NOT NULL,
  contenu TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_message),
  KEY fk_message_deal (id_deal),
  CONSTRAINT fk_message_deal
    FOREIGN KEY (id_deal) REFERENCES deal_request(id_deal)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- 5) Avis clients
-- =========================

CREATE TABLE review (
  id_review INT NOT NULL AUTO_INCREMENT,
  id_deal INT NOT NULL,
  client_username VARCHAR(30) NOT NULL,
  vendeur_username VARCHAR(30) NOT NULL,
  rating TINYINT NOT NULL,
  commentaire TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_review),
  KEY fk_review_deal (id_deal),
  KEY fk_review_client (client_username),
  KEY fk_review_vendeur (vendeur_username),
  CONSTRAINT fk_review_deal
    FOREIGN KEY (id_deal) REFERENCES deal_request(id_deal)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_review_client
    FOREIGN KEY (client_username) REFERENCES client(username)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT fk_review_vendeur
    FOREIGN KEY (vendeur_username) REFERENCES vendeur(username)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =========================
-- 6) Données d'exemple
-- =========================

INSERT INTO client (username, email, adresse, num_tel, idphoto, password) VALUES
('eyaabbes', 'eya.abbes@insat.ucar.tn', 'Sfax route menzel cheker km3.5', '29204518', '../files_profil/eya.jpg', 'eyaabbes01'),
('skander', 'skander@email.com', 'Tunis centre', '22334455', '../files_profil/skander.jpg', 'skander123'),
('amira', 'amira@email.com', 'Nabeul', '55667788', '../files_profil/amira.jpg', 'amira123');

INSERT INTO vendeur (username, email, adresse, num_tel, idphoto, password) VALUES
('phone_store', 'store@email.com', 'Lafayette Tunis', '94367714', '../files_profil/store.jpg', 'store123'),
('tech_shop', 'tech@email.com', 'Sousse', '99887766', '../files_profil/tech.jpg', 'tech123');

INSERT INTO demande (nom_produit, prix, lien_produit, description, categorie, id_photo, username, etat) VALUES
('Cadre photo', 25.00, 'http://exemple.com/cadre-photo', 'Je veux ce produit avant le 06/04', 'tous', '../files_demande/cadre.jpg', 'eyaabbes', 'en attente'),
('iPhone 13', 1800.00, 'http://exemple.com/iphone13', 'Je cherche un iPhone 13 en bon état', 'smartphone', '../files_demande/iphone13.jpg', 'skander', 'en attente'),
('AirPods Pro', 450.00, 'http://exemple.com/airpods-pro', 'Je veux des écouteurs originaux', 'accessoire', '../files_demande/airpods.jpg', 'amira', 'en attente');

INSERT INTO produit (vendeur_username, nom_produit, prix, categorie, description, image_path) VALUES
('phone_store', 'Samsung Galaxy S23', 2450.00, 'smartphone', 'Téléphone neuf avec garantie', '../files_produits/s23.jpg'),
('phone_store', 'iPhone 14 Pro', 3200.00, 'smartphone', 'Excellent état, 128 Go', '../files_produits/iphone14pro.jpg'),
('tech_shop', 'AirPods 3', 520.00, 'accessoire', 'Original Apple', '../files_produits/airpods3.jpg'),
('tech_shop', 'Chargeur USB-C 25W', 70.00, 'accessoire', 'Charge rapide Samsung', '../files_produits/chargeur25w.jpg');

INSERT INTO deal_request (id_demande, client_username, vendeur_username, prix_propose, message, status) VALUES
(1, 'eyaabbes', 'phone_store', 22.00, 'Bonjour, je peux vous livrer avant le 06/04.', 'en attente'),
(2, 'skander', 'phone_store', 1750.00, 'iPhone 13 disponible, très bon état.', 'accepté'),
(3, 'amira', 'tech_shop', 430.00, 'Je peux vous proposer un modèle original.', 'en attente');

INSERT INTO message (id_deal, sender_username, receiver_username, contenu) VALUES
(1, 'phone_store', 'eyaabbes', 'Bonjour, votre demande est disponible.'),
(1, 'eyaabbes', 'phone_store', 'Merci, combien de jours pour la livraison ?'),
(2, 'skander', 'phone_store', 'Parfait, je confirme la commande.');

INSERT INTO review (id_deal, client_username, vendeur_username, rating, commentaire) VALUES
(2, 'skander', 'phone_store', 5, 'Très bon vendeur, livraison rapide.');

COMMIT;

