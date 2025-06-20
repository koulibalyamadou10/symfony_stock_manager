# 📦 Application de Gestion de Stock

**Projet académique** réalisé dans le cadre de la Licence 3 à l’Université Gamal Abdel Nasser de Conakry (UGANC), Faculté du Centre Informatique.

- **Technologie principale** : Symfony 7
- **Durée estimée** : 2 à 3 jours
- **Auteur** : Koulibaly Amadou
- **Encadrement** : UGANC - Centre Informatique

---

## 🎯 Objectif du projet

Développer une application web intuitive et sécurisée permettant :
- Aux utilisateurs simples de consulter les produits disponibles.
- Aux administrateurs de gérer les produits, les catégories et les utilisateurs via une interface adaptée.

---

## 🧩 Description Générale

L'application est structurée autour de deux rôles :

- **Administrateur (`ROLE_ADMIN`)** :
  - Gère les produits, catégories, et utilisateurs.
  - Accès total à l'application.

- **Utilisateur simple (`ROLE_USER`)** :
  - Accès limité à la consultation des produits actifs.
  - Actions restreintes selon ses autorisations.

L'application respecte l'architecture MVC, avec un design responsive et une séparation claire de la logique métier.

---

## 🚀 Fonctionnalités principales

### 🔐 Authentification & Rôles
- Connexion / inscription avec contrôle de rôles
- Sécurisation des routes via le firewall Symfony

### 📦 Gestion des entités (CRUD)
- **Produits** : création, édition, suppression (admin uniquement)
- **Catégories** : gestion complète (admin uniquement)
- **Utilisateurs** : gestion restreinte (via EasyAdmin ou manuel)

### 🔁 Relations entre entités
- Produit → Catégorie : `ManyToOne`
- Catégorie → Produits : `OneToMany`

### 🎯 Affichages dynamiques
- Affichage des produits **actifs uniquement**
- Interface et options dynamiques selon les rôles

### ⚙️ Logique métier
- Diminution automatique de la quantité après une vente
- Désactivation automatique du produit quand le stock atteint zéro

### 👤 Données liées à l’utilisateur
- Tableau de bord personnalisé
- Visualisation des produits ajoutés par l'utilisateur (si applicable)

### ✅ Bonnes pratiques
- Utilisation de services Symfony
- Validation des formulaires
- Flash messages pour succès/erreurs
- Code clair, modulaire et réutilisable

---

## 🗂️ Entités principales

| Entité      | Champs principaux                                          |
|-------------|------------------------------------------------------------|
| Utilisateur | nom, email, mot de passe, rôle                             |
| Catégorie   | id, nom, description                                       |
| Produit     | id, nom, prix, quantité, actif, date_ajout, catégorie_id   |

---

## 🛠️ Technologies utilisées

- **Symfony 7**
- **PHP 8+**
- **Twig** pour le rendu des vues
- **Doctrine ORM** pour la persistance des données
- **MySQL** 
- **Bootstrap 5** pour un design responsive

---

## 🧪 Installation & Exécution

1. Cloner le projet :

   ```bash
   git clone https://github.com/koulibalyamadou10/symfony_stock_manager
   cd symfony_stock_manager

2. Copier le fichier .env a la racine de ce projet

3. Executer le fichier sql qui se trouve dans le dossier

4. Installer les dependances
composer install

5. Verifier l'url de connection
DATABASE_URL="mysql://root:6218@127.0.0.1:3306/stock_manager_symfony?serverVersion=8.0.32&charset=utf8mb4"

6. Executer les migrations
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate
