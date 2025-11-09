# Documentation Technique - YamsDrive Backend

**Rapport de Stage - Architecture et Fonctionnalités**

---

## Table des Matières

1. [Vue d'ensemble du projet](#1-vue-densemble-du-projet)
2. [Architecture technique](#2-architecture-technique)
3. [Enchaînement logique de la programmation](#3-enchaînement-logique-de-la-programmation)
4. [Entités et relations](#4-entités-et-relations)
5. [Fonctionnalités principales](#5-fonctionnalités-principales)
6. [Sécurité et authentification](#6-sécurité-et-authentification)

---

## 1. Vue d'ensemble du projet

**YamsDrive** est une application de gestion électronique de documents (GED) développée avec Laravel 11. Elle permet aux utilisateurs de stocker, partager et gérer leurs documents de manière sécurisée et collaborative.

### Technologies utilisées
- **Framework**: Laravel 11
- **Base de données**: MySQL
- **Authentification**: Laravel Sanctum (API tokens)
- **Stockage**: Système de fichiers local (Laravel Storage)
- **Architecture**: API RESTful

---

## 2. Architecture technique

### Structure MVC Laravel

Le projet suit l'architecture MVC (Model-View-Controller) de Laravel:

```
app/
├── Http/
│   └── Controllers/     # Logique métier et endpoints API
├── Models/              # Modèles Eloquent (entités)
├── Events/              # Événements système
├── Listeners/           # Écouteurs d'événements
├── Traits/              # Code réutilisable
└── Mail/                # Templates d'emails

database/
└── migrations/          # Schéma de base de données

routes/
└── api.php             # Définition des routes API
```

### Composants clés

- **Controllers**: Gèrent les requêtes HTTP et orchestrent la logique métier
- **Models**: Représentent les entités et gèrent les relations
- **Migrations**: Définissent la structure de la base de données
- **Traits**: Fournissent des fonctionnalités réutilisables (ex: LogActionTrait)
- **Events/Listeners**: Système de journalisation asynchrone

---

## 3. Enchaînement logique de la programmation

### Phase 1: Fondations (Juin-Juillet 2025)

#### 1.1 Création de la base de données
- **Services** (`2025_06_17_110457`): Table pour organiser les utilisateurs par départements/équipes
- **Emails** (`2025_06_17_112100`): Système de messagerie interne
- **LogActions** (`2025_06_17_124622`): Journalisation des actions utilisateurs

#### 1.2 Authentification et utilisateurs
- **Users**: Table principale avec champs métier (nom, prénom, email, service)
- **Sessions**: Gestion des sessions utilisateur
- **Password Reset**: Système de réinitialisation de mot de passe

### Phase 2: Gestion documentaire (Juillet-Août 2025)

#### 2.1 Documents de base
- **Documents** (`2025_07_17_153840`): Création de la table principale
  - Champs: nom, chemin, type, taille, description
  - Relations: user_id (propriétaire), service_id (rattachement)

#### 2.2 Identification unique
- **UUID** (`2025_07_17_160436`): Ajout d'identifiants UUID pour les documents
  - Permet des URLs stables et sécurisées
  - Évite l'exposition des IDs séquentiels

#### 2.3 Partage de documents
- **DocumentShares** (`2025_07_21_235804`): Partage ciblé entre utilisateurs
  - Permissions: read/edit
  - Expiration optionnelle
  - Token unique par partage

#### 2.4 Favoris
- **Favoris** (`2025_07_29_134011`): Marquage de documents favoris
  - Accès rapide aux documents importants
  - Relation many-to-many user-document

### Phase 3: Optimisation et sécurité (Août 2025)

#### 3.1 Performance
- **Indexes** (`2025_08_10_153327`): Ajout d'index sur les colonnes fréquemment requêtées
  - `created_at` sur documents, shares, logs
  - Index composite `(action, created_at)` sur logs

#### 3.2 Amélioration des logs
- **Text columns** (`2025_08_11_114350`): Changement des colonnes de logs en TEXT
  - Permet le stockage de données volumineuses (JSON)

#### 3.3 Soft deletes (Corbeille)
- **Soft Deletes** (`2025_08_22_090207`): Ajout de `deleted_at` sur documents
  - Suppression logique (corbeille)
  - Possibilité de restauration
  - Protection contre les suppressions accidentelles

### Phase 4: Fonctionnalités avancées (Septembre 2025)

#### 4.1 Organisation par services
- **Service_id sur Users** (`2025_09_15_134052`): Rattachement des utilisateurs aux services
  - Permet le filtrage et l'organisation
  - Facilite le partage par service

#### 4.2 Partage par lien
- **ShareLinks** (`2025_09_15_220137`): Partage via URL publique
  - Token sécurisé de 64 caractères
  - Expiration configurable
  - Permissions read/edit
  - Partage avec des personnes externes

---

## 4. Entités et relations

### 4.1 Entités principales (utilisées activement)

#### **Users** (Utilisateurs)
Table centrale du système d'authentification et de gestion des utilisateurs.

**Champs principaux:**
- `id`: Identifiant unique
- `nom`, `prenom`: Identité de l'utilisateur
- `email`: Identifiant de connexion (unique)
- `password`: Mot de passe hashé
- `role`: Rôle de l'utilisateur (admin, user, etc.)
- `statut`: État du compte (active, inactive)
- `service_id`: Rattachement à un service (FK)

**Relations:**
- **1→N** avec `documents` (propriétaire)
- **1→N** avec `document_shares` (partages reçus et émis)
- **1→N** avec `favoris`
- **N→1** avec `services`

#### **Documents**
Entité centrale pour la gestion des fichiers.

**Champs principaux:**
- `id`: Identifiant unique
- `uuid`: Identifiant UUID (pour URLs)
- `nom`: Nom du fichier
- `chemin`: Chemin de stockage
- `type`: Type MIME
- `taille`: Taille en octets
- `description`: Description optionnelle
- `user_id`: Propriétaire (FK)
- `service_id`: Service rattaché (FK, nullable)
- `deleted_at`: Soft delete (corbeille)

**Relations:**
- **N→1** avec `users` (propriétaire)
- **N→1** avec `services` (rattachement)
- **1→N** avec `document_shares` (partages)
- **1→N** avec `share_links` (liens publics)
- **1→N** avec `favoris`

#### **Services**
Organisation des utilisateurs et documents par départements/équipes.

**Champs:**
- `id`: Identifiant unique
- `nom`: Nom du service
- `description`: Description
- `prix`: Tarification (si applicable)

**Relations:**
- **1→N** avec `users`
- **1→N** avec `documents`

#### **DocumentShares** (Partages ciblés)
Gestion du partage de documents entre utilisateurs internes.

**Champs:**
- `id`: Identifiant unique
- `document_id`: Document partagé (FK)
- `user_id`: Bénéficiaire du partage (FK)
- `shared_by`: Auteur du partage (FK users)
- `permission_level`: Niveau de permission (read/edit)
- `token`: Token unique (nullable)
- `expires_at`: Date d'expiration (nullable)

**Contraintes:**
- Unique composite `(document_id, user_id)`

**Relations:**
- **N→1** avec `documents`
- **N→1** avec `users` (bénéficiaire)
- **N→1** avec `users` (auteur via shared_by)

#### **ShareLinks** (Partages publics)
Partage de documents via liens publics sécurisés.

**Champs:**
- `id`: Identifiant unique
- `document_id`: Document partagé (FK)
- `token`: Token unique de 64 caractères
- `shared_by`: Auteur du partage (FK users)
- `permission_level`: Niveau de permission (read/edit)
- `expires_at`: Date d'expiration (nullable)

**Relations:**
- **N→1** avec `documents`
- **N→1** avec `users` (auteur)

#### **Favoris**
Marquage de documents favoris par utilisateur.

**Champs:**
- `id`: Identifiant unique
- `user_id`: Utilisateur (FK)
- `document_id`: Document favori (FK)

**Relations:**
- **N→1** avec `users` (cascade delete)
- **N→1** avec `documents` (cascade delete)

#### **LogActions** (Journalisation)
Traçabilité des actions effectuées dans le système.

**Champs:**
- `id`: Identifiant unique
- `action`: Type d'action (création, mise à jour, suppression, etc.)
- `table_affectee`: Table concernée
- `user_id`: Utilisateur ayant effectué l'action (nullable)
- `nouvelles_valeurs`: État après modification (JSON, TEXT)
- `anciennes_valeurs`: État avant modification (JSON, TEXT)
- `adresse_ip`: Adresse IP de l'utilisateur
- `deleted_at`: Soft delete

**Index:**
- `created_at`
- Composite `(action, created_at)`

### 4.2 Schéma relationnel

```
┌─────────────┐
│   Services  │
│  (services) │
└──────┬──────┘
       │
       │ 1:N
       │
┌──────┴──────┐         ┌──────────────┐
│    Users    │────────>│  Documents   │
│   (users)   │  1:N    │ (documents)  │
└──────┬──────┘         └──────┬───────┘
       │                       │
       │ 1:N                   │ 1:N
       │                       │
       ├───────────────────────┼──────────┐
       │                       │          │
       v                       v          v
┌──────────────┐      ┌─────────────┐  ┌────────────┐
│DocumentShares│      │ ShareLinks  │  │  Favoris   │
│(doc_shares)  │      │(share_links)│  │ (favoris)  │
└──────────────┘      └─────────────┘  └────────────┘

┌──────────────┐
│  LogActions  │
│ (log_actions)│
└──────────────┘
```

**Légende:**
- **Flèches**: Relations avec clés étrangères
- **1:N**: Un à plusieurs
- **N:1**: Plusieurs à un

---

## 5. Fonctionnalités principales

### 5.1 Authentification et gestion des utilisateurs

#### Inscription (AuthController::registerUser)
**Endpoint:** `POST /api/utilisateurs/new`

**Processus:**
1. Validation des données (nom, prénom, email, service_id)
2. Génération automatique d'un mot de passe aléatoire (8 caractères)
3. Création d'un username unique basé sur la date/heure
4. Hashage du mot de passe avec bcrypt
5. Enregistrement en base de données
6. Envoi d'un email avec les identifiants

**Sécurité:**
- Email unique (contrainte base de données)
- Mot de passe hashé (jamais stocké en clair)
- Validation des données d'entrée

#### Connexion (AuthController::authenticate)
**Endpoint:** `POST /api/token`

**Processus:**
1. Validation des credentials (email + password)
2. Tentative d'authentification via Laravel Auth
3. Génération d'un token Sanctum
4. Retour du token + données utilisateur

**Réponse:**
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "nom": "Dupont",
    "prenom": "Jean",
    "email": "jean.dupont@example.com",
    "role": "user",
    "service_id": 2
  }
}
```

#### Déconnexion (LogoutController::logout)
**Endpoint:** `POST /api/logout`

**Processus:**
1. Récupération du token actuel
2. Suppression du token de la base de données
3. Invalidation de la session

**Protection:** Middleware `auth:sanctum`

### 5.2 Gestion des documents

#### Upload de document (DocumentController::store)
**Endpoint:** `POST /api/documents`

**Processus:**
1. Validation du fichier (max 10MB)
2. Génération d'un UUID pour le nom de fichier
3. Stockage dans `storage/app/public/documents/`
4. Extraction des métadonnées (nom, type MIME, taille)
5. Création de l'enregistrement en base de données
6. Déclenchement de l'événement de journalisation

**Données stockées:**
- UUID unique pour identification
- Nom original du fichier
- Chemin de stockage
- Type MIME
- Taille en octets
- Description (optionnelle)
- Propriétaire (user_id)
- Service rattaché (service_id, optionnel)

#### Liste des documents (DocumentController::index)
**Endpoint:** `GET /api/documents`

**Retour:**
- Documents appartenant à l'utilisateur connecté
- Avec relations: service
- Triés par date de création

#### Détails d'un document (DocumentController::show)
**Endpoint:** `GET /api/documents/{uuid}`

**Retour:**
- Métadonnées complètes du document
- Informations du service rattaché
- Propriétaire du document

#### Téléchargement (DocumentController::download)
**Endpoint:** `GET /api/documents/{uuid}/download`

**Processus:**
1. Vérification des permissions (propriétaire ou partage)
2. Récupération du fichier depuis le storage
3. Envoi avec headers appropriés (Content-Type, Content-Disposition)

#### Prévisualisation (DocumentController::preview)
**Endpoint:** `GET /api/documents/{uuid}/preview`

**Fonctionnalité:**
- Affichage inline du document (PDF, images)
- Streaming pour les fichiers volumineux

#### Mise à jour (DocumentController::update)
**Endpoint:** `POST /api/documents/{uuid}/update`

**Champs modifiables:**
- Nom du document
- Description
- Service rattaché

**Journalisation:**
- Capture des anciennes et nouvelles valeurs
- Enregistrement dans log_actions

#### Suppression (Corbeille)

##### Soft Delete (DocumentController::destroy)
**Endpoint:** `DELETE /api/documents/{uuid}`

**Processus:**
1. Marquage du document avec `deleted_at`
2. Document déplacé dans la corbeille
3. Possibilité de restauration

##### Liste de la corbeille (DocumentController::trash)
**Endpoint:** `GET /api/documents/trash`

**Retour:** Documents supprimés (soft deleted) de l'utilisateur

##### Restauration (DocumentController::restore)
**Endpoint:** `POST /api/documents/{uuid}/restore`

**Processus:**
1. Suppression du flag `deleted_at`
2. Document réintégré dans la liste principale

##### Suppression définitive (DocumentController::forceDelete)
**Endpoint:** `DELETE /api/documents/{uuid}/force`

**Processus:**
1. Suppression physique du fichier sur le disque
2. Suppression de l'enregistrement en base de données
3. Action irréversible

### 5.3 Partage de documents

#### Partage ciblé entre utilisateurs (DocumentShareController::share)
**Endpoint:** `POST /api/documents/{uuid}/share`

**Paramètres:**
- `user_id`: ID de l'utilisateur destinataire
- `permission_level`: "read" ou "edit"
- `expires_at`: Date d'expiration (optionnelle)

**Processus:**
1. Vérification que l'utilisateur est propriétaire du document
2. Vérification de l'existence de l'utilisateur destinataire
3. Création de l'enregistrement DocumentShare
4. Génération d'un token unique

**Contraintes:**
- Un document ne peut être partagé qu'une fois avec le même utilisateur
- Contrainte unique `(document_id, user_id)` en base

#### Partage par service (ServiceShareController::shareByService)
**Endpoint:** `POST /api/documents/{uuid}/share-by-service`

**Fonctionnalité:**
- Partage d'un document avec tous les utilisateurs d'un service
- Création de multiples DocumentShares en une seule opération

#### Génération de lien public (DocumentShareController::generateShareLink)
**Endpoint:** `POST /api/documents/{uuid}/share-link`

**Paramètres:**
- `permission_level`: "read" ou "edit"
- `expires_at`: Date d'expiration (optionnelle)

**Processus:**
1. Génération d'un token sécurisé de 64 caractères
2. Création de l'enregistrement ShareLink
3. Retour de l'URL complète de partage

**URL générée:** `https://app.com/shared-documents/{token}`

#### Accès via lien public (DocumentShareController::accessSharedDocument)
**Endpoint:** `GET /api/shared-documents/{token}`

**Processus:**
1. Recherche du ShareLink par token
2. Vérification de l'expiration
3. Vérification des permissions
4. Retour des métadonnées du document
5. Possibilité de téléchargement selon les permissions

**Sécurité:**
- Token unique et aléatoire
- Expiration configurable
- Permissions granulaires (read/edit)
- Pas d'authentification requise

#### Documents partagés avec moi (DocumentShareController::sharedWithMe)
**Endpoint:** `GET /api/documents/shared-with-me`

**Retour:**
- Liste des documents partagés avec l'utilisateur connecté
- Informations sur le partage (auteur, permissions, expiration)
- Filtrage par nom de document ou propriétaire

**Filtres disponibles:**
- `q`: Recherche textuelle (nom du document)
- `owner`: Filtrage par nom du propriétaire

#### Liste des partages d'un document (DocumentShareController::index)
**Endpoint:** `GET /api/documents/{uuid}/shares`

**Retour:**
- Liste des utilisateurs avec qui le document est partagé
- Niveau de permission de chaque partage
- Date d'expiration

#### Suppression d'un partage (DocumentShareController::removeShare)
**Endpoint:** `DELETE /api/documents/{uuid}/shares/{shareId}`

**Processus:**
1. Vérification que l'utilisateur est propriétaire
2. Suppression de l'enregistrement DocumentShare
3. Révocation de l'accès

### 5.4 Favoris

#### Ajout aux favoris (FavoriController::store)
**Endpoint:** `POST /api/favoris`

**Paramètres:**
- `document_id`: ID du document

**Processus:**
1. Vérification que le document existe
2. Vérification que le document n'est pas déjà en favori
3. Création de l'enregistrement Favori

**Protection:** Événements désactivés pour éviter la récursion

#### Liste des favoris (FavoriController::index)
**Endpoint:** `GET /api/favoris`

**Retour:**
- Liste des documents favoris de l'utilisateur
- Sans relations pour éviter la récursion

#### Retrait des favoris (FavoriController::destroy)
**Endpoint:** `DELETE /api/favoris/{document_id}`

**Processus:**
1. Recherche du favori
2. Suppression de l'enregistrement

### 5.5 Recherche et filtrage

#### Recherche globale (DocumentSearchController::index)
**Endpoint:** `GET /api/documents/search`

**Critères de recherche:**
- `q`: Recherche textuelle (nom, description)
- `type`: Filtrage par type MIME
- `service_id`: Filtrage par service
- `owner`: Filtrage par propriétaire
- `date_from`, `date_to`: Plage de dates

**Périmètre:**
- Documents de l'utilisateur
- Documents partagés avec l'utilisateur

#### Documents récents (DocumentController::getRecentDocuments)
**Endpoint:** `GET /api/documents/recent`

**Retour:**
- Documents créés ou modifiés récemment
- Limite configurable (par défaut: 10)

#### Options de filtres (DocumentFilterController::getFilterOptions)
**Endpoint:** `GET /api/documents/filters/options`

**Retour:**
- Liste des types de documents disponibles
- Liste des services
- Liste des utilisateurs (pour filtrage par propriétaire)

### 5.6 Statistiques et tableaux de bord

#### Statistiques globales (StatistiqueController::getGlobalStats)
**Endpoint:** `GET /api/statistiques/globales`

**Métriques:**
- Nombre total d'utilisateurs
- Nombre total de documents
- Nombre total de partages

#### Activité des documents (StatistiqueController::getDocumentActivity)
**Endpoint:** `GET /api/statistiques/activite-documents`

**Données:**
- Documents créés par mois (6 derniers mois)
- Documents partagés par mois
- Format: Graphique en ligne

#### Répartition du stockage (StatistiqueController::getStorageBreakdown)
**Endpoint:** `GET /api/statistiques/repartition-stockage`

**Données:**
- Taille totale par type de document
- Taille totale par service
- Format: Graphique circulaire

#### Activité des utilisateurs (StatistiqueController::getUserActivity)
**Endpoint:** `GET /api/statistiques/activite-utilisateurs`

**Données:**
- Nombre de documents par utilisateur
- Nombre de partages par utilisateur
- Utilisateurs les plus actifs

#### Actions récentes (StatistiqueController::getRecentActions)
**Endpoint:** `GET /api/statistiques/actions-recentes`

**Données:**
- Dernières actions enregistrées dans log_actions
- Limite: 50 actions
- Informations: utilisateur, action, date, IP

---

## 6. Sécurité et authentification

### 6.1 Laravel Sanctum

**Principe:**
- Authentification basée sur des tokens API
- Tokens stockés dans la table `personal_access_tokens`
- Chaque token est associé à un utilisateur

**Workflow:**
1. L'utilisateur se connecte avec email/password
2. Le serveur génère un token unique
3. Le client stocke le token (localStorage, cookie)
4. Le client envoie le token dans chaque requête (header Authorization)
5. Le middleware `auth:sanctum` valide le token

**Avantages:**
- Stateless (pas de session serveur)
- Révocation facile des tokens
- Expiration configurable
- Support multi-devices

### 6.2 Middleware d'authentification

**Routes protégées:**
```php
Route::middleware('auth:sanctum')->group(function () {
    // Routes nécessitant une authentification
});
```

**Vérification:**
1. Extraction du token depuis le header `Authorization: Bearer {token}`
2. Recherche du token dans `personal_access_tokens`
3. Vérification de l'expiration
4. Chargement de l'utilisateur associé
5. Injection dans `Auth::user()`

### 6.3 Permissions et autorisations

**Niveaux de permission:**
- **read**: Consultation uniquement (téléchargement, prévisualisation)
- **edit**: Modification des métadonnées (nom, description)

**Vérifications:**
1. Propriétaire du document: tous les droits
2. Document partagé avec permission "read": consultation uniquement
3. Document partagé avec permission "edit": consultation + modification
4. Lien public: selon le niveau défini lors de la création

**Implémentation:**
```php
// Vérification dans les contrôleurs
if ($document->user_id !== Auth::id() && !$document->isSharedWithUser(Auth::id())) {
    return response()->json(['message' => 'Non autorisé'], 403);
}
```

### 6.4 Sécurité des fichiers

**Stockage:**
- Fichiers stockés hors de la racine web (`storage/app/public/`)
- Accès uniquement via les contrôleurs (pas d'accès direct)
- Noms de fichiers randomisés (UUID)

**Validation:**
- Taille maximale: 10MB
- Types de fichiers: tous types acceptés (configurable)
- Scan antivirus recommandé (non implémenté)

**Téléchargement sécurisé:**
1. Vérification des permissions
2. Génération de headers appropriés
3. Streaming pour les gros fichiers
4. Pas de cache côté client pour les documents sensibles

### 6.5 Protection contre les attaques

**CSRF:**
- Protection native de Laravel
- Tokens CSRF pour les formulaires web
- API exemptée (authentification par token)

**XSS:**
- Échappement automatique des données dans les templates
- Validation des entrées utilisateur
- Sanitization des données JSON

**SQL Injection:**
- Utilisation d'Eloquent ORM
- Requêtes préparées automatiques
- Validation des paramètres

**Rate Limiting:**
- Limitation du nombre de requêtes par IP
- Throttling sur les endpoints sensibles (login, register)

### 6.6 Système de journalisation

**Architecture:**
- **LogActionTrait**: Trait réutilisable pour la journalisation
- **UserActionLogged**: Événement déclenché lors d'une action
- **LogUserAction**: Listener qui enregistre l'événement
- **LogAction**: Modèle pour stocker les logs

**Données enregistrées:**
- Action effectuée (création, mise à jour, suppression, etc.)
- Table affectée
- ID de l'utilisateur
- Nouvelles valeurs (JSON)
- Anciennes valeurs (JSON)
- Adresse IP
- Timestamp

**Protection contre la récursion:**
1. **withoutEvents()**: Désactivation temporaire des événements
2. **Vérification du modèle**: Ne pas logger les logs
3. **Dispatcher temporaire**: Suppression et restauration du dispatcher

**Utilisation:**
- Audit de sécurité
- Diagnostic de problèmes
- Traçabilité réglementaire
- Analyse d'activité

---

## Conclusion

Ce backend Laravel implémente une solution complète de GED avec:

✅ **Gestion documentaire robuste**: Upload, stockage, métadonnées, corbeille  
✅ **Partage flexible**: Ciblé (utilisateurs), par service, liens publics  
✅ **Sécurité renforcée**: Authentification Sanctum, permissions granulaires  
✅ **Traçabilité complète**: Journalisation de toutes les actions  
✅ **Performance optimisée**: Index, soft deletes, requêtes optimisées  
✅ **Architecture évolutive**: MVC, événements, traits réutilisables  

### Points forts du projet

1. **Architecture progressive**: Développement incrémental avec phases logiques
2. **Sécurité par conception**: UUID, soft deletes, permissions granulaires
3. **Collaboration facilitée**: Multiples modes de partage (ciblé, service, public)
4. **Traçabilité**: Système de logs complet pour audit et diagnostic
5. **Expérience utilisateur**: Favoris, recherche avancée, statistiques

### Technologies maîtrisées

- **Laravel 11**: Framework PHP moderne
- **Eloquent ORM**: Gestion des relations et requêtes
- **Sanctum**: Authentification API sécurisée
- **Events/Listeners**: Architecture événementielle
- **Migrations**: Versioning de la base de données
- **Storage**: Gestion des fichiers
- **Validation**: Sécurisation des entrées

### Évolutions possibles

- Versioning des documents (historique des modifications)
- Prévisualisation avancée (Office, CAD, vidéos)
- Scan antivirus automatique lors de l'upload
- Chiffrement des fichiers sensibles
- Notifications en temps réel (WebSockets)
- Compression automatique des fichiers
- OCR pour l'indexation du contenu
- Intégration avec des services cloud (S3, Google Drive)

---

**Document rédigé pour le rapport de stage**  
**Projet: YamsDrive - Backend Laravel**  
**Date: Octobre 2025**
