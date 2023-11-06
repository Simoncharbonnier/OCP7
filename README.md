# BileMo API

Ce projet est réalisé dans le cadre de la formation de développeur d'application PHP/Symfony chez OpenClassrooms.

La mission est de créer un web service exposant une API pour le premier client de l'entreprise BileMo.

Voici les différentes technologies utilisées dans ce projet :
-   Symfony - PHP


## Installation

Cloner mon projet

```bash
git clone https://github.com/Simoncharbonnier/OCP7.git
```

Installer les dépendances avec Composer

```bash
composer install
```

Générer les clés pour l'authentification

```bash
php bin/console lexik:jwt:generate-keypair
```

Configurer les variables d'environnement dans le fichier .env ou .env.local

```bash
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=14&charset=utf8"
JWT_PASSPHRASE=
```

Créer la base de données

```bash
php bin/console doctrine:database:create
```

Créer les tables de la base de données

```bash
php bin/console doctrine:schema:update --force
```

Insérer un jeu de données

```bash
php bin/console doctrine:fixtures:load
```

Lancer Symfony

```bash
symfony server:start
```

Accéder à la documentation

```bash
http://localhost:8000/api/doc
```

Et tout devrait fonctionner sans soucis !


## Fonctionnalités

-   En tant que visiteur, je peux consulter la documentation.

-   En tant que client, je peux me connecter et récupérer un token.
-   En tant que client, je peux consulter la liste des produits BileMo.
-   En tant que client, je peux consulter les détails d'un produit BileMo.
-   En tant que client, je peux consulter la liste des utilisateurs inscrits sur mon site web.
-   En tant que client, je peux consulter les détails d'un utilisateur inscrit sur mon site web.
-   En tant que client, je peux ajouter un utilisateur inscrit sur mon site web.
-   En tant que client, je peux modifier un de mes utilisateurs.
-   En tant que client, je peux supprimer un de mes utilisateurs.
