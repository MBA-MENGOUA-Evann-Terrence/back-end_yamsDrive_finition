# Mémoire Technique - Développement du Backend YamsDrive
## Partie 1 : Introduction, Présentation et Architecture

---

## 1. Introduction générale

Dans le contexte actuel de transformation numérique des entreprises, la gestion électronique des documents (GED) s'impose comme un enjeu majeur pour optimiser les processus métier et faciliter la collaboration entre les équipes. Les organisations sont confrontées à des volumes croissants de documents numériques qui nécessitent des solutions robustes pour leur stockage, leur partage et leur traçabilité.

Ce mémoire présente la conception et le développement du backend de **YamsDrive**, une application web de gestion électronique de documents développée dans le cadre de mon stage. L'objectif principal de ce projet était de créer une solution complète permettant aux utilisateurs de stocker, organiser, partager et gérer leurs documents de manière sécurisée et collaborative.

Le développement de cette application a nécessité la mise en œuvre de compétences variées en développement backend, notamment la conception de bases de données relationnelles, la création d'API RESTful, la gestion de l'authentification et des autorisations, ainsi que l'implémentation de mécanismes de sécurité robustes.

---

## 2. Présentation du projet

### 2.1 Contexte et objectifs

YamsDrive a été conçu pour répondre aux besoins des entreprises en matière de gestion documentaire collaborative. L'application vise à centraliser le stockage des documents tout en offrant des fonctionnalités avancées de partage et de collaboration entre les utilisateurs.

Les objectifs principaux du projet étaient les suivants :

Premièrement, il s'agissait de développer une API RESTful performante et sécurisée pour la gestion des documents. Cette API devait respecter les standards REST et fournir des endpoints cohérents pour toutes les opérations CRUD (Create, Read, Update, Delete) sur les différentes entités du système.

Deuxièmement, nous devions implémenter un système d'authentification robuste basé sur des tokens. Ce système devait permettre une authentification sécurisée des utilisateurs tout en facilitant l'intégration avec différents types de clients (applications web, mobiles, ou tierces).

Troisièmement, il fallait créer un système de partage flexible permettant différents modes de collaboration. Les utilisateurs devaient pouvoir partager des documents de manière ciblée avec d'autres utilisateurs internes, par service, ou via des liens publics sécurisés.

Quatrièmement, nous devions assurer la traçabilité complète des actions effectuées sur les documents. Chaque opération significative devait être enregistrée dans un système de logs permettant l'audit et le diagnostic en cas de problème.

Cinquièmement, il était nécessaire d'optimiser les performances pour gérer de gros volumes de données. L'application devait rester réactive même avec des milliers de documents et d'utilisateurs.

Enfin, nous devions garantir la sécurité des données et des fichiers stockés en implémentant les meilleures pratiques de sécurité web.

### 2.2 Technologies retenues

Pour la réalisation de ce projet, nous avons opté pour une stack technologique moderne et éprouvée.

**Framework backend :** Laravel 11 a été choisi comme framework PHP principal en raison de sa maturité, de sa documentation exhaustive et de son écosystème riche. Laravel offre une architecture MVC claire qui facilite l'organisation du code et la séparation des responsabilités. Son ORM Eloquent simplifie considérablement les interactions avec la base de données en permettant de manipuler les données comme des objets PHP plutôt que d'écrire des requêtes SQL brutes. De plus, Laravel intègre nativement de nombreux outils facilitant le développement rapide d'applications web robustes : système de routing, validation des données, gestion des sessions, envoi d'emails, et bien d'autres.

**Base de données :** MySQL a été retenu comme système de gestion de base de données relationnelle pour sa fiabilité éprouvée, ses excellentes performances et sa compatibilité native avec Laravel. La structure relationnelle permet de modéliser efficacement les relations complexes entre les entités du système (utilisateurs, documents, partages, etc.) tout en garantissant l'intégrité référentielle grâce aux contraintes de clés étrangères.

**Authentification :** Laravel Sanctum a été implémenté pour gérer l'authentification API. Cette solution légère et moderne, spécialement conçue pour les applications SPA (Single Page Application) et mobiles, permet de générer des tokens d'accès personnels pour chaque session utilisateur. Contrairement aux solutions plus lourdes comme OAuth2, Sanctum offre une simplicité d'implémentation tout en maintenant un niveau de sécurité élevé. Les tokens générés sont stockés de manière sécurisée et peuvent être révoqués individuellement, offrant ainsi un contrôle granulaire sur les accès.

**Stockage des fichiers :** Le système de stockage de Laravel, basé sur la bibliothèque Flysystem, a été utilisé pour gérer les fichiers uploadés. Cette abstraction présente l'avantage majeur de découpler la logique métier du système de stockage physique. Actuellement, les fichiers sont stockés localement sur le serveur, mais cette architecture permettrait une migration transparente vers des solutions cloud comme Amazon S3 ou Google Cloud Storage sans modification du code applicatif.

### 2.3 Architecture globale

L'application suit une architecture API REST qui sépare clairement le backend du frontend. Cette approche architecturale présente plusieurs avantages significatifs pour le développement et la maintenance de l'application.

Le **découplage** entre le backend et le frontend permet à chaque couche d'évoluer indépendamment. Les développeurs frontend peuvent travailler sur l'interface utilisateur sans impacter le backend, et inversement. Cette séparation facilite également la collaboration au sein d'équipes de développement distinctes.

La **réutilisabilité** de l'API constitue un autre avantage majeur. Une fois l'API développée, elle peut être consommée par différents types de clients : application web, application mobile iOS ou Android, ou même des applications tierces via un système d'API keys. Cette flexibilité permet d'étendre la portée de l'application sans réécrire la logique métier.

La **scalabilité** est grandement facilitée par cette architecture. Le backend et le frontend peuvent être déployés sur des serveurs différents et mis à l'échelle indépendamment selon les besoins. Si l'API subit une charge importante, on peut augmenter les ressources serveur dédiées au backend sans toucher au frontend.

La **testabilité** est également améliorée. Les endpoints API peuvent être testés de manière isolée via des tests unitaires et d'intégration, sans nécessiter l'interface utilisateur. Cette approche facilite la détection précoce des bugs et garantit la stabilité de l'application.

Le backend expose des endpoints RESTful qui respectent les conventions HTTP standard. Les opérations de lecture utilisent la méthode GET, les créations utilisent POST, les mises à jour utilisent PUT ou PATCH, et les suppressions utilisent DELETE. Toutes les réponses sont retournées au format JSON, facilitant leur consommation par n'importe quel client capable de parser du JSON.

Chaque requête API nécessitant une authentification doit inclure un token Bearer dans les headers HTTP. Ce token, généré lors de la connexion, identifie l'utilisateur et permet au backend de vérifier ses permissions avant d'exécuter l'opération demandée.

---

## 3. Architecture et choix techniques

### 3.1 Pattern MVC (Model-View-Controller)

L'application respecte rigoureusement le pattern architectural MVC imposé par Laravel, ce qui permet une séparation claire des responsabilités et facilite la maintenance du code.

**Les Modèles (Models)** représentent les entités métier et encapsulent toute la logique d'accès aux données. Chaque modèle correspond à une table de la base de données et définit les relations avec les autres entités. Par exemple, le modèle `Document` définit ses relations avec les modèles `User` (propriétaire), `Service` (rattachement), `DocumentShare` (partages), et `Favori` (favoris). L'utilisation d'Eloquent ORM simplifie considérablement les opérations CRUD et la gestion des relations. Au lieu d'écrire des requêtes SQL complexes avec des jointures, nous pouvons simplement accéder aux relations via des propriétés d'objet, rendant le code plus lisible et maintenable.

**Les Contrôleurs (Controllers)** orchestrent la logique métier de l'application. Ils constituent le point d'entrée des requêtes HTTP et coordonnent les interactions entre les différents composants. Un contrôleur typique reçoit une requête, valide les données d'entrée en utilisant le système de validation de Laravel, interagit avec un ou plusieurs modèles pour effectuer les opérations nécessaires (lecture, création, mise à jour, suppression), puis retourne une réponse appropriée au client. Chaque contrôleur est responsable d'un domaine fonctionnel spécifique : `DocumentController` gère les opérations sur les documents, `AuthController` gère l'authentification, `DocumentShareController` gère les partages, etc. Cette organisation modulaire facilite la navigation dans le code et la localisation des fonctionnalités.

**Les Vues (Views)** dans le contexte d'une API REST sont représentées par les réponses JSON structurées retournées aux clients. Contrairement à une application web traditionnelle qui génère du HTML, notre API retourne des données brutes au format JSON que le client frontend se charge de présenter à l'utilisateur. Ces réponses suivent un format cohérent facilitant leur consommation : un objet JSON contenant généralement une clé `data` pour les données métier, et éventuellement des clés `message` pour les messages informatifs et `errors` pour les erreurs de validation.

### 3.2 Organisation du code

La structure du projet suit les conventions Laravel tout en étant organisée de manière à faciliter la maintenance et l'évolution. Chaque type de fichier a son emplacement dédié, rendant le projet navigable même pour un développeur découvrant le code pour la première fois.

Le répertoire `app/Http/Controllers` contient tous les contrôleurs gérant les endpoints API. Chaque contrôleur est nommé de manière explicite selon sa responsabilité : `DocumentController`, `UserController`, `FavoriController`, etc.

Le répertoire `app/Models` regroupe tous les modèles Eloquent représentant les entités métier. Ces modèles définissent les attributs mass-assignables, les relations avec d'autres modèles, et éventuellement des méthodes utilitaires spécifiques à l'entité.

Le répertoire `app/Events` contient les classes d'événements qui sont déclenchés lors d'actions significatives dans l'application. Par exemple, l'événement `UserActionLogged` est émis chaque fois qu'une action doit être journalisée.

Le répertoire `app/Listeners` contient les écouteurs d'événements qui réagissent aux événements émis. Le listener `LogUserAction` écoute l'événement `UserActionLogged` et enregistre l'action dans la base de données.

Le répertoire `app/Traits` regroupe les traits réutilisables qui peuvent être inclus dans différentes classes. Le trait `LogActionTrait` fournit par exemple une méthode standardisée pour journaliser les actions.

Le répertoire `database/migrations` contient toutes les migrations qui définissent la structure de la base de données. Chaque migration est horodatée, permettant à Laravel de les exécuter dans l'ordre chronologique correct.

Le fichier `routes/api.php` définit tous les endpoints de l'API avec leurs méthodes HTTP, leurs URLs, et les contrôleurs associés. Ce fichier constitue la carte de navigation de l'API.

Cette organisation modulaire facilite non seulement la navigation dans le code, mais également la collaboration entre développeurs et la maintenance à long terme de l'application.
