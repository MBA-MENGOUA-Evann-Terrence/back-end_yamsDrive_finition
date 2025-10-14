# Documentation Base de Données (YamsDrive)

Dernière mise à jour: 2025-10-08

Ce document décrit le schéma de la base de données du projet, en se concentrant sur les tables métier (cœur du projet) et en identifiant les tables techniques ou hors périmètre.

Sources: migrations dans `database/migrations/`.

---

## 1) Vue d’ensemble fonctionnelle 

- Le projet implémente une GED/Drive: gestion de documents, partage entre utilisateurs et via lien, favoris, journalisation des actions, rattachement optionnel à un service.
- Tables cœur: `users`, `documents`, `document_shares`, `share_links`, `favoris`, `log_actions`, `services`.
- Tables techniques/Laravel ou périphériques: `sessions`, `password_reset_tokens`, `personal_access_tokens`, `cache`, `cache_locks`, `telescope_entries*`, et potentiellement `domains`, `emails` si non utilisées par l’app.

### Ce que fait la base concrètement (vue métier)

Cette base de données implémente un Drive/GED multi‑utilisateurs axé sur:

- Gestion des documents
  - Chaque document (`documents`) stocke ses métadonnées (nom, type, taille, description) et son chemin de stockage.
  - Il appartient à un utilisateur (`documents.user_id`) et peut être rattaché à un service (`documents.service_id`) pour organiser par unité/équipe.
  - Les documents utilisent les soft deletes (corbeille) et un `uuid` unique pour des partages/URLs stables.

- Partage et collaboration
  - Partage ciblé entre comptes internes via `document_shares` avec un niveau de permission (`read` ou `edit`) et possible expiration.
  - Partage par lien public sécurisé via `share_links` (token unique, expiration, permission).

- Personnalisation utilisateur
  - Chaque utilisateur peut marquer des documents en favoris (`favoris`) pour un accès rapide.
  - Les utilisateurs et documents peuvent être catégorisés par `services` pour filtrage, reporting, et cloisonnement organisationnel.

- Traçabilité et sécurité
  - Les actions significatives sont journalisées dans `log_actions` (qui, quoi, avant/après, quand, depuis quelle IP) pour audit et diagnostic.

- Support technique
  - Authentification et sessions (`sessions`, `password_reset_tokens`) et, si nécessaire, accès API via `personal_access_tokens` (Sanctum).
  - Observabilité et dev/ops via `telescope_entries*` (si activé).

Exemples de flux pris en charge:

- Dépôt → Partage → Consultation
  - Un utilisateur téléverse un document, le rattache à son service, le partage en lecture à un collègue ou génère un lien public temporaire.

- Organisation → Favoris → Recherche
  - Les documents sont filtrés par propriétaire, service ou date; les favoris accélèrent l’accès aux fichiers clés.

- Corbeille → Restauration
  - Une suppression place le document en corbeille (soft delete) avec possibilité de restauration.

- Audit → Sécurité
  - En cas d’incident, `log_actions` permet de retracer qui a fait quoi et quand, pour investiguer rapidement.

---

## 2) Tables cœur du projet (métier)

### users
- Migration principale: `0001_01_01_000000_create_users_table.php`
- Colonnes: 
  - `id` (PK), `nom`, `prenom`(nullable), `email`(unique), `telephone1/2`(nullable), `role`(nullable), `statut`(default 'active'), `permissions`(json, nullable), `email_verified_at`(nullable), `password`, `name` (devenue nullable via `2025_08_14_115013_make_name_field_nullable_in_users_table.php`), `date_naissance`(nullable), `adresse`(nullable), `signature`(nullable), `remember_token`, timestamps.
- Relations:
  - 1–N avec `documents` via `documents.user_id` (cascade delete).
  - 1–N avec `document_shares` via `user_id` (bénéficiaire) et `shared_by` (auteur).
  - Optionnelle 1–N avec `services` via `users.service_id` (`2025_09_15_134052_add_service_to_users_table.php`).
- Tables techniques créées dans la même migration: `password_reset_tokens`, `sessions` (voir section 3).

### services
- Migration: `2025_06_17_110457_create_services_table.php`
- Colonnes: `id` (PK), `nom`(nullable), `description`(nullable), `prix`(nullable), timestamps, soft deletes.
- Relations:
  - 1–N avec `users` (nullable, on delete set null).
  - 1–N avec `documents` (nullable, on delete set null).

### documents
- Migrations: 
  - Création: `2025_07_17_153840_create_documents_table.php`
  - Ajout `uuid`: `2025_07_17_160436_add_uuid_to_documents_table.php`
  - Soft delete (sécurisation): `2025_08_22_090207_add_deleted_at_to_documents_table.php`
- Colonnes: `id` (PK), `uuid` (unique, not null), `nom`, `chemin`, `type`, `taille`(bigint), `description`(nullable), `user_id` (FK), `service_id` (FK, nullable), timestamps, soft deletes.
- Relations: 
  - `user_id` → `users.id` (cascade delete).
  - `service_id` → `services.id` (nullable, on delete set null).
- Index: `created_at` indexé (`2025_08_10_153327_add_indexes_for_stats_performance.php`).

### document_shares
- Migration: `2025_07_21_235804_create_document_shares_table.php`
- Colonnes: `id` (PK), `document_id` (FK), `user_id` (FK, bénéficiaire), `shared_by` (FK, auteur), `permission_level` enum('read','edit') default 'read', `token`(nullable, unique), `expires_at`(nullable), timestamps.
- Contraintes: unique composite `(document_id, user_id)`.
- Rôle: partage ciblé document → utilisateur.

### share_links
- Migration: `2025_09_15_220137_create_share_links_table.php`
- Colonnes: `id` (PK), `document_id` (FK), `token` (64, unique), `shared_by` (FK users), `permission_level` enum('read','edit') default 'read', `expires_at`(nullable), timestamps.
- Rôle: partage via lien tokenisé (option expiration), avec niveau de permission.

### favoris
- Migration: `2025_07_29_134011_create_favoris_table.php`
- Colonnes: `id` (PK), `user_id` (FK, cascade), `document_id` (FK, cascade), timestamps.
- Rôle: marquage de documents favoris par utilisateur.

### log_actions
- Migrations: `2025_06_17_124622_create_log_actions_table.php` + `2025_08_11_114350_change_log_actions_columns_to_text.php`
- Colonnes: `id` (PK), `action`(nullable), `table_affectee`(nullable), `user_id`(nullable), `nouvelles_valeurs`(text, nullable), `anciennes_valeurs`(text, nullable), `adresse_ip`(nullable), timestamps, soft deletes.
- Index: `created_at`, et `(action, created_at)` (`2025_08_10_153327_add_indexes_for_stats_performance.php`).
- Rôle: audit/logging des actions et des mutations de données.

---

## 3) Tables techniques/support (non cœur métier)

### sessions
- Définie dans `0001_01_01_000000_create_users_table.php`.
- Colonnes: `id` (PK string), `user_id` (nullable, index), `ip_address`(nullable), `user_agent`(nullable), `payload`, `last_activity`(index).
- Rôle: stockage des sessions (si driver database).

### password_reset_tokens
- Définie dans `0001_01_01_000000_create_users_table.php`.
- Colonnes: `email` (PK), `token`, `created_at`(nullable).
- Rôle: réinitialisation de mot de passe.

### personal_access_tokens (Sanctum)
- Migration: `2025_06_24_112729_create_personal_access_tokens_table.php`.
- Colonnes principales: `id` (PK), `tokenable_type/id` (morphs), `name`, `token`(unique), `abilities`(nullable), `last_used_at`(nullable), `expires_at`(nullable), timestamps.
- Rôle: jetons API personnels (si Sanctum est utilisé).

### cache, cache_locks
- Migration: `0001_01_01_000001_create_cache_table.php`.
- Rôle: mécanismes de cache/lock Laravel (technique).

### telescope_entries, telescope_entries_tags, telescope_monitoring
- Migration: `2025_08_12_230237_create_telescope_entries_table.php`.
- Rôle: monitoring Laravel Telescope (dev/ops).

### domains
- Migrations: `2025_08_22_131500_create_domains_table.php`, `2025_08_22_133509_make_user_id_nullable_in_domains_table.php`.
- Colonnes: `id` (PK), `name`(unique), `user_id` (FK nullable), `status` default 'pending', `expires_at`(nullable), timestamps.
- Rôle: gestion de domaines (feature potentielle). Hors périmètre si non utilisée par l’app.

### emails
- Migration: `2025_06_17_112100_create_emails_table.php`.
- Colonnes: `id` (PK), `objet`(nullable), `message`(nullable), `statut`(nullable), `type`(nullable), `date_envoi`(nullable), `date_lecture`(nullable), `prospect_id`(nullable), `user_id`(nullable), timestamps, soft deletes.
- Rôle: messagerie/notifications (hors périmètre si non branché aux flux actuels).

---

## 4) Relations et intégrité référentielle (récapitulatif)

- `documents.user_id` → `users.id` (on delete cascade)
- `documents.service_id` → `services.id` (nullable, on delete set null)
- `document_shares.document_id` → `documents.id` (cascade)
- `document_shares.user_id` → `users.id` (cascade)
- `document_shares.shared_by` → `users.id` (cascade)
- `favoris.user_id` → `users.id` (cascade)
- `favoris.document_id` → `documents.id` (cascade)
- `share_links.document_id` → `documents.id` (cascade)
- `share_links.shared_by` → `users.id` (cascade)
- `users.service_id` → `services.id` (nullable, on delete set null)
- `domains.user_id` → `users.id` (nullable)
- Unicité/Index notables: `users.email` unique, `documents.uuid` unique, `document_shares (document_id, user_id)` unique, indexes sur `created_at` (documents, document_shares, log_actions) et `(action, created_at)`.

---

## 5) Schéma ER (ASCII simplifié)

```
User (users)
  id PK, email UNIQUE, ... , service_id FK -> services.id
         |\
         | \ 1..N
         |  \------------------------------.
         |                                 \
         |                                  \
         v                                   v
Service (services)                      Document (documents)
  id PK                                   id PK, uuid UNIQUE
                                          user_id FK -> users.id (CASCADE)
                                          service_id FK -> services.id (SET NULL)
                                          ...

DocumentShare (document_shares)
  id PK, document_id FK -> documents.id (CASCADE)
         user_id FK -> users.id (CASCADE)       (bénéficiaire)
         shared_by FK -> users.id (CASCADE)     (auteur)
  UNIQUE (document_id, user_id)

ShareLink (share_links)
  id PK, document_id FK -> documents.id (CASCADE)
        token UNIQUE, shared_by FK -> users.id (CASCADE)

Favori (favoris)
  id PK, user_id FK -> users.id (CASCADE)
        document_id FK -> documents.id (CASCADE)

LogAction (log_actions)
  id PK, user_id (nullable), action, table_affectee, ...
```

---

## 6) Indices et performance
- Index ajoutés: `created_at` sur `documents`, `document_shares`, `log_actions` et `(action, created_at)` sur `log_actions`.
- Recommandations supplémentaires (selon requêtes réelles):
  - Index sur `documents.user_id`, `documents.service_id`.
  - Index sur `favoris.user_id`, `favoris.document_id`.
  - Index sur `document_shares.document_id`, `document_shares.user_id`.

---

## 7) Pistes d’amélioration
- Sécurité/ACL: vérifier l’application stricte des niveaux `permission_level` côté contrôleurs/services.
- Typage clés étrangères: dans `emails`, préférer `foreignId`/`unsignedBigInteger` + contraintes si relation réelle.
- Nettoyage: si `domains`/`emails` ne sont pas utilisés, documenter leur statut expérimental ou les retirer pour alléger le schéma.

---

## 8) Annexes (référence migrations)
- `users`: `0001_01_01_000000_create_users_table.php`, `2025_08_14_115013_make_name_field_nullable_in_users_table.php`, `2025_09_15_134052_add_service_to_users_table.php`
- `documents`: `2025_07_17_153840_create_documents_table.php`, `2025_07_17_160436_add_uuid_to_documents_table.php`, `2025_08_22_090207_add_deleted_at_to_documents_table.php`, `2025_08_10_153327_add_indexes_for_stats_performance.php`
- `document_shares`: `2025_07_21_235804_create_document_shares_table.php`, `2025_08_10_153327_add_indexes_for_stats_performance.php`
- `share_links`: `2025_09_15_220137_create_share_links_table.php`
- `favoris`: `2025_07_29_134011_create_favoris_table.php`
- `log_actions`: `2025_06_17_124622_create_log_actions_table.php`, `2025_08_11_114350_change_log_actions_columns_to_text.php`, `2025_08_10_153327_add_indexes_for_stats_performance.php`
- `services`: `2025_06_17_110457_create_services_table.php`
- Techniques: `0001_01_01_000001_create_cache_table.php`, `2025_06_24_112729_create_personal_access_tokens_table.php`, `2025_08_12_230237_create_telescope_entries_table.php`
- Potentielles: `2025_08_22_131500_create_domains_table.php`, `2025_08_22_133509_make_user_id_nullable_in_domains_table.php`, `2025_06_17_112100_create_emails_table.php`

---

Fin du document.
