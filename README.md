# YamsDrive - Votre Solution de Gestion de Documents

**YamsDrive** est une application de gestion électronique de documents (GED) sécurisée et collaborative, développée avec Laravel 11. Elle permet aux utilisateurs et aux équipes de stocker, gérer, partager et retrouver leurs documents de manière efficace.

---

## Cas d'Utilisation (Use Cases)

Voici ce que vous pouvez faire avec YamsDrive :

### 1. En tant qu'Administrateur

-   **Gérer les utilisateurs** : Créer de nouveaux comptes utilisateurs. Un mot de passe est généré automatiquement et envoyé par email.
-   **Organiser les équipes** : Créer et gérer des "Services" (départements) pour structurer les utilisateurs et les documents.
-   **Superviser l'activité** : Accéder à des statistiques globales (nombre d'utilisateurs, de documents) et consulter le journal de toutes les actions effectuées sur la plateforme pour un audit complet.

### 2. En tant qu'Utilisateur

-   **S'authentifier** : Se connecter de manière sécurisée pour accéder à son espace personnel.
-   **Gérer ses documents** :
    -   **Uploader** : Ajouter de nouveaux documents (fichiers, images, PDF, etc.).
    -   **Organiser** : Voir la liste de ses documents, les modifier (nom, description) et les organiser.
    -   **Retrouver** : Rechercher des documents par nom, type, date ou propriétaire.
    -   **Accéder rapidement** : Marquer des documents comme "Favoris" pour les retrouver facilement.
-   **Utiliser la corbeille** :
    -   **Supprimer** un document (il est d'abord déplacé dans la corbeille).
    -   **Restaurer** un document depuis la corbeille.
    -   **Supprimer définitivement** un document.

### 3. Pour la Collaboration

-   **Partager avec d'autres utilisateurs** :
    -   Partager un document avec un ou plusieurs utilisateurs spécifiques de la plateforme.
    -   Définir des permissions pour chaque partage : **lecture seule** (`read`) ou **modification** (`edit`).
    -   Fixer une date d'expiration pour un partage.
-   **Partager avec des personnes externes** :
    -   Générer un **lien de partage public et sécurisé** pour un document.
    -   Contrôler les permissions (`read`/`edit`) et la date d'expiration du lien.
-   **Partager avec une équipe** :
    -   Partager un document instantanément avec tous les membres d'un "Service".
-   **Consulter les partages** :
    -   Voir la liste des documents que d'autres ont partagés avec vous.
    -   Voir qui a accès à vos propres documents.

---

## Technologies Utilisées

-   **Framework** : Laravel 11
-   **Base de données** : MySQL
-   **Authentification** : Laravel Sanctum (API RESTful)
-   **Stockage de fichiers** : Système de fichiers local de Laravel

---

## Installation du Projet

1.  **Cloner le dépôt** :
    ```bash
    git clone https://votre-repository/back-end_yamsDrive-main.git
    cd back-end_yamsDrive-main
    ```

2.  **Installer les dépendances** :
    ```bash
    composer install
    npm install
    ```

3.  **Configurer l'environnement** :
    -   Copiez le fichier `.env.example` en `.env` : `cp .env.example .env`
    -   Générez une clé d'application : `php artisan key:generate`
    -   Configurez les informations de votre base de données dans le fichier `.env` (DB_DATABASE, DB_USERNAME, DB_PASSWORD).

4.  **Lancer les migrations** :
    ```bash
    php artisan migrate
    ```

5.  **Lancer le serveur** :
    ```bash
    php artisan serve
    ```

L'API sera accessible à l'adresse `http://127.0.0.1:8000`.
