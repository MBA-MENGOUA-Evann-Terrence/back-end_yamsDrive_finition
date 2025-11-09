# Mémoire Technique - Développement du Backend YamsDrive
## Partie 3 : Implémentation des fonctionnalités et Sécurité

---

## 6. Implémentation des fonctionnalités

L'implémentation des fonctionnalités a été réalisée en suivant les principes SOLID et les bonnes pratiques de développement Laravel. Chaque fonctionnalité a été conçue pour être maintenable, testable et évolutive. Cette section détaille les principales fonctionnalités implémentées et leur fonctionnement technique.

### 6.1 Authentification et gestion des utilisateurs

#### 6.1.1 Processus d'inscription

Le processus d'inscription des nouveaux utilisateurs a été conçu pour être à la fois sécurisé et convivial, tout en minimisant les risques d'erreur. Lorsqu'un administrateur crée un nouveau compte utilisateur via l'endpoint `POST /api/utilisateurs/new`, le système effectue une série d'opérations coordonnées.

Tout d'abord, les données fournies (nom, prénom, email, service de rattachement) sont rigoureusement validées. Laravel fournit un système de validation puissant et expressif qui vérifie non seulement la présence des champs obligatoires, mais également leur format et leur unicité. Par exemple, l'email doit respecter le format standard d'une adresse email et ne pas déjà exister dans la base de données. Si la validation échoue, une réponse d'erreur détaillée est retournée au client, indiquant précisément quels champs sont problématiques et pourquoi.

Une fois les données validées, le système génère automatiquement un mot de passe aléatoire sécurisé de 8 caractères combinant lettres, chiffres et caractères spéciaux. Ce mot de passe est immédiatement hashé avec l'algorithme bcrypt avant d'être stocké en base de données. Bcrypt est un algorithme de hachage adaptatif spécialement conçu pour les mots de passe, qui intègre un facteur de coût permettant d'ajuster la difficulté du calcul. Cette approche garantit qu'aucun mot de passe n'est jamais conservé en clair, même temporairement, protégeant ainsi les utilisateurs en cas de compromission de la base de données.

Un nom d'utilisateur unique est également généré automatiquement en combinant le préfixe "user" avec un timestamp au format année-mois-jour-heure-minute-seconde. Cette méthode assure l'unicité de chaque nom d'utilisateur sans nécessiter d'intervention manuelle.

Une fois le compte créé en base de données, un email automatique est envoyé à l'utilisateur contenant ses identifiants de connexion. Cet email est généré à partir d'un template Blade et envoyé via le système de mail de Laravel, qui peut être configuré pour utiliser différents services d'envoi (SMTP, Mailgun, SendGrid, etc.). Cette approche permet à l'utilisateur de se connecter immédiatement tout en lui donnant la possibilité de modifier son mot de passe lors de sa première connexion pour en choisir un qu'il mémorisera plus facilement.

#### 6.1.2 Processus de connexion et génération de tokens

Le processus d'authentification repose sur Laravel Sanctum, une solution moderne et sécurisée spécialement conçue pour les API. Lorsqu'un utilisateur tente de se connecter via l'endpoint `POST /api/token`, le système vérifie d'abord que l'email et le mot de passe fournis correspondent à un compte existant.

Laravel utilise son système d'authentification intégré qui compare le hash du mot de passe fourni avec celui stocké en base de données. Cette comparaison utilise une fonction de vérification sécurisée qui prend un temps constant quelle que soit la correspondance, évitant ainsi les attaques par analyse temporelle. Si les credentials sont invalides, une réponse d'erreur générique est retournée sans indiquer si c'est l'email ou le mot de passe qui est incorrect, empêchant ainsi l'énumération des comptes existants.

Si les credentials sont valides, le système génère un token d'accès personnel unique via Sanctum. Ce token est une chaîne aléatoire cryptographiquement sécurisée qui est stockée dans la table `personal_access_tokens` avec un hash SHA-256. Le token en clair est retourné au client une seule fois lors de la génération, puis seul son hash est conservé en base de données. Cette approche garantit que même en cas de compromission de la base de données, les tokens ne peuvent pas être réutilisés directement.

Le token est ensuite retourné au client accompagné des informations utilisateur (nom, prénom, email, rôle, service). Le client doit stocker ce token de manière sécurisée (généralement dans le localStorage ou un cookie HttpOnly) et l'inclure dans toutes ses requêtes futures via le header HTTP `Authorization: Bearer {token}`.

Cette approche stateless présente plusieurs avantages significatifs. Elle permet de gérer facilement plusieurs sessions simultanées : un utilisateur peut être connecté depuis son ordinateur de bureau, son téléphone et sa tablette, chaque appareil possédant son propre token. La révocation des accès est également simplifiée : un utilisateur peut se déconnecter d'un appareil spécifique en supprimant uniquement le token correspondant, sans affecter ses autres sessions actives. Enfin, cette architecture facilite la mise à l'échelle horizontale de l'application, car aucune session n'est stockée côté serveur.

#### 6.1.3 Gestion des sessions et déconnexion

La déconnexion est gérée de manière simple mais efficace via l'endpoint `POST /api/logout`. Lorsqu'un utilisateur se déconnecte, le système récupère le token d'accès actuel depuis le header Authorization, puis supprime ce token spécifique de la table `personal_access_tokens`. Cette suppression invalide immédiatement toutes les requêtes futures utilisant ce token, forçant l'utilisateur à se reconnecter pour obtenir un nouveau token.

Cette approche permet une déconnexion sélective particulièrement utile dans un contexte multi-appareils. Par exemple, si un utilisateur perd son téléphone, il peut se connecter depuis son ordinateur et révoquer spécifiquement le token de son téléphone, sans affecter sa session sur ordinateur. De plus, un administrateur peut révoquer tous les tokens d'un utilisateur en cas de compromission de compte, forçant ainsi une réauthentification complète sur tous les appareils.

### 6.2 Gestion complète des documents

#### 6.2.1 Upload et stockage des fichiers

Le processus d'upload de documents a été conçu pour être robuste, sécurisé et performant. Lorsqu'un utilisateur téléverse un fichier via l'endpoint `POST /api/documents`, plusieurs étapes sont exécutées de manière coordonnée.

La validation du fichier constitue la première ligne de défense contre les abus et les attaques. Le système vérifie que le fichier ne dépasse pas la taille maximale autorisée, configurée par défaut à 10 MB. Cette limite protège le serveur contre les attaques par saturation de l'espace disque et garantit des temps d'upload raisonnables pour les utilisateurs. La limite peut être ajustée selon les besoins de l'organisation et les capacités du serveur.

Le fichier est ensuite stocké dans un répertoire dédié (`storage/app/public/documents/`) avec un nom généré aléatoirement basé sur un UUID version 4. Cette approche présente plusieurs avantages cruciaux pour la sécurité et la maintenance. Elle évite les conflits de noms qui pourraient survenir si deux utilisateurs uploadent des fichiers portant le même nom. Elle empêche la prédiction des chemins de fichiers, rendant impossible l'accès direct aux fichiers sans passer par l'API. Elle facilite l'organisation du stockage en évitant les problèmes de caractères spéciaux ou de longueur excessive dans les noms de fichiers. Le nom original du fichier est conservé dans la base de données pour être présenté à l'utilisateur, offrant ainsi une expérience familière tout en maintenant une structure de stockage propre.

Les métadonnées du fichier sont automatiquement extraites lors de l'upload. Le type MIME est déterminé en analysant le contenu réel du fichier (et non simplement son extension), offrant une protection supplémentaire contre les fichiers malveillants déguisés. La taille exacte en octets est enregistrée pour les statistiques et la gestion des quotas. Ces métadonnées sont stockées dans la table `documents` avec un UUID unique servant d'identifiant public.

Cette séparation entre le stockage physique et les métadonnées offre une grande flexibilité architecturale. Le fichier physique pourrait être déplacé vers un autre système de stockage (Amazon S3, Google Cloud Storage, etc.) sans affecter les références dans l'application, simplement en modifiant la configuration du système de stockage Laravel.

Enfin, un événement `UserActionLogged` est déclenché pour tracer l'opération d'upload dans les logs système. Cet événement est capturé par un listener qui enregistre de manière asynchrone les détails de l'action, permettant un audit complet sans ralentir la réponse à l'utilisateur.

#### 6.2.2 Consultation et téléchargement sécurisés

La consultation des documents implémente un système de permissions granulaire garantissant que seuls les utilisateurs autorisés peuvent accéder aux fichiers. Avant de permettre l'accès à un document via l'endpoint `GET /api/documents/{uuid}`, le système effectue une vérification en plusieurs étapes.

Premièrement, le document est recherché dans la base de données en utilisant son UUID. Si le document n'existe pas ou a été supprimé (soft delete), une erreur 404 est retournée. Deuxièmement, le système vérifie que l'utilisateur authentifié est soit le propriétaire du document, soit un bénéficiaire d'un partage actif (non expiré) avec les permissions appropriées. Cette vérification utilise les relations Eloquent pour interroger efficacement les tables `documents` et `document_shares`. Si l'utilisateur n'a pas les permissions nécessaires, une erreur 403 (Forbidden) est retournée.

Pour le téléchargement via l'endpoint `GET /api/documents/{uuid}/download`, le système récupère le fichier depuis le stockage et le transmet au client avec les headers HTTP appropriés. Le header `Content-Type` est défini selon le type MIME du fichier, permettant au navigateur de traiter correctement le fichier. Le header `Content-Disposition` est configuré en mode `attachment` avec le nom original du fichier, forçant le navigateur à télécharger le fichier plutôt que de l'afficher inline. Le header `Content-Length` indique la taille du fichier, permettant au navigateur d'afficher une barre de progression précise.

La prévisualisation des documents via l'endpoint `GET /api/documents/{uuid}/preview` est particulièrement utile pour les fichiers PDF et images. Le système utilise le streaming pour transmettre les fichiers volumineux de manière efficace, évitant de charger l'intégralité du fichier en mémoire. Le header `Content-Disposition` est configuré en mode `inline`, permettant au navigateur d'afficher le fichier directement plutôt que de le télécharger. Cette fonctionnalité améliore significativement l'expérience utilisateur en permettant une consultation rapide sans téléchargement préalable.

#### 6.2.3 Modification et suppression

La mise à jour des métadonnées d'un document via l'endpoint `POST /api/documents/{uuid}/update` est réservée au propriétaire ou aux utilisateurs ayant reçu la permission d'édition. Les champs modifiables incluent le nom du document, sa description et son service de rattachement. Avant chaque modification, le système capture l'état actuel du document en utilisant la méthode `toArray()` d'Eloquent. Après la modification, les changements sont enregistrés et un événement de journalisation est déclenché avec à la fois les anciennes et les nouvelles valeurs. Cette approche permet de maintenir un historique complet des modifications, facilitant l'audit et le diagnostic de problèmes.

La suppression des documents implémente un système à trois niveaux offrant flexibilité et sécurité. La suppression logique via l'endpoint `DELETE /api/documents/{uuid}` marque simplement le document avec une date dans le champ `deleted_at`, le déplaçant conceptuellement dans une corbeille. Le document reste physiquement présent mais est automatiquement exclu des requêtes normales grâce au trait `SoftDeletes` d'Eloquent. Cette approche protège contre les suppressions accidentelles tout en libérant visuellement l'espace pour l'utilisateur.

Les documents en corbeille peuvent être consultés via l'endpoint `GET /api/documents/trash` et restaurés via `POST /api/documents/{uuid}/restore`. La restauration supprime simplement la date du champ `deleted_at`, réintégrant le document dans la liste normale.

Enfin, la suppression définitive via l'endpoint `DELETE /api/documents/{uuid}/force` effectue une suppression physique complète. Le fichier est supprimé du système de stockage et l'enregistrement est effacé de la base de données. Cette action irréversible est généralement réservée aux administrateurs et nécessite une confirmation explicite de l'utilisateur.

### 6.3 Système de partage collaboratif

#### 6.3.1 Partage ciblé entre utilisateurs

Le partage ciblé via l'endpoint `POST /api/documents/{uuid}/share` permet à un propriétaire de document de le partager avec des utilisateurs spécifiques de l'organisation. Le processus vérifie d'abord que l'utilisateur effectuant la demande est bien le propriétaire du document. Ensuite, il valide que l'utilisateur destinataire existe dans le système. Un enregistrement est créé dans la table `document_shares` avec le niveau de permission spécifié (read ou edit) et une éventuelle date d'expiration.

La contrainte d'unicité composite sur `(document_id, user_id)` empêche les doublons. Si un partage existe déjà, l'utilisateur peut le mettre à jour pour modifier les permissions ou l'expiration. Cette approche simplifie la gestion des partages en évitant les situations ambiguës où un document serait partagé plusieurs fois avec le même utilisateur avec des permissions différentes.

#### 6.3.2 Partage par service

Le partage par service via l'endpoint `POST /api/documents/{uuid}/share-by-service` étend le concept de partage ciblé en permettant de partager un document avec tous les membres d'un service en une seule opération. Le système récupère tous les utilisateurs appartenant au service spécifié et crée automatiquement un partage pour chacun d'eux. Cette fonctionnalité améliore significativement l'efficacité du partage dans les organisations structurées, évitant d'avoir à sélectionner manuellement des dizaines d'utilisateurs.

#### 6.3.3 Partage par lien public

Le partage par lien public via l'endpoint `POST /api/documents/{uuid}/share-link` génère une URL publique sécurisée permettant l'accès au document sans authentification. Le système génère un token aléatoire de 64 caractères en utilisant des fonctions cryptographiquement sécurisées, garantissant l'unicité et l'imprévisibilité du lien. Ce token est stocké dans la table `share_links` avec le niveau de permission et l'expiration configurés.

L'URL générée suit le format `https://app.com/shared-documents/{token}` et peut être partagée par email, messagerie instantanée ou tout autre moyen. Lorsqu'un utilisateur accède à cette URL via l'endpoint `GET /api/shared-documents/{token}`, le système vérifie que le lien existe, n'est pas expiré, puis retourne les métadonnées du document et permet son téléchargement selon les permissions définies.

Cette fonctionnalité s'avère particulièrement utile pour partager des documents avec des clients, des partenaires ou des prestataires externes, sans nécessiter la création de comptes utilisateurs. Les dates d'expiration permettent de créer des accès temporaires qui se révoquent automatiquement, renforçant la sécurité.

---

## 7. Sécurité et authentification

La sécurité constitue un aspect fondamental de l'application, particulièrement critique pour une solution de gestion documentaire manipulant potentiellement des données sensibles. Nous avons implémenté plusieurs couches de sécurité couvrant l'authentification, l'autorisation, la protection des données et la défense contre les attaques courantes.

### 7.1 Authentification par tokens avec Laravel Sanctum

Laravel Sanctum fournit un système d'authentification léger mais robuste spécialement conçu pour les API. Le workflow complet garantit la sécurité des accès tout en maintenant une expérience utilisateur fluide.

Lors de la connexion, l'utilisateur fournit son email et son mot de passe. Le système vérifie ces credentials en comparant le hash du mot de passe fourni avec celui stocké en base de données. Cette comparaison utilise l'algorithme bcrypt qui intègre un sel unique pour chaque mot de passe, rendant impossible l'utilisation de tables arc-en-ciel pour craquer les mots de passe même en cas de compromission de la base de données.

Si les credentials sont valides, un token unique est généré en utilisant des fonctions cryptographiquement sécurisées. Ce token est stocké dans la table `personal_access_tokens` sous forme hashée avec SHA-256. Le token en clair est retourné au client une seule fois, puis seul son hash est conservé. Cette approche garantit que même si la base de données est compromise, les tokens ne peuvent pas être réutilisés directement.

Le client stocke le token de manière sécurisée et l'inclut dans chaque requête via le header `Authorization: Bearer {token}`. Le middleware `auth:sanctum` intercepte chaque requête, extrait le token, le hashe, puis recherche le hash correspondant dans la table `personal_access_tokens`. Si le token est valide et non expiré, l'utilisateur associé est chargé et injecté dans le contexte de la requête via `Auth::user()`.

Cette architecture stateless présente plusieurs avantages pour la sécurité. Les tokens peuvent être révoqués individuellement en les supprimant de la base de données, permettant une déconnexion sélective par appareil. Les tokens peuvent avoir une durée de vie limitée, forçant une réauthentification périodique. Aucune session n'est stockée côté serveur, éliminant les risques liés aux attaques de fixation de session.

### 7.2 Système de permissions granulaires

Le système de permissions implémente un contrôle d'accès fin sur les documents. Chaque document possède un propriétaire qui dispose de tous les droits (consultation, modification, suppression, partage). Les documents partagés accordent des permissions spécifiques selon le niveau défini lors du partage.

La permission "read" autorise uniquement la consultation et le téléchargement du document. L'utilisateur peut voir les métadonnées et accéder au contenu, mais ne peut pas modifier le document ou le partager à son tour. Cette permission convient pour un partage en lecture seule, par exemple pour communiquer une information sans risque de modification.

La permission "edit" autorise la consultation, le téléchargement et la modification des métadonnées du document (nom, description, service). L'utilisateur ne peut cependant pas supprimer le document ni modifier ses permissions de partage, ces opérations restant réservées au propriétaire. Cette permission convient pour une collaboration où plusieurs personnes doivent pouvoir enrichir les métadonnées du document.

Ces vérifications de permissions sont implémentées dans chaque contrôleur concerné. Avant d'effectuer une opération, le système vérifie systématiquement que l'utilisateur authentifié possède les droits nécessaires. Si ce n'est pas le cas, une erreur 403 (Forbidden) est retournée avec un message explicite. Cette approche défensive garantit qu'aucune opération non autorisée ne peut être effectuée, même en cas d'erreur dans l'interface utilisateur.

### 7.3 Sécurité du stockage des fichiers

Le stockage des fichiers a été conçu avec plusieurs mécanismes de sécurité. Les fichiers sont stockés dans un répertoire hors de la racine web (`storage/app/public/`), empêchant tout accès direct via une URL. L'accès aux fichiers passe obligatoirement par les contrôleurs qui vérifient les permissions avant de servir le fichier.

Les noms de fichiers sont randomisés en utilisant des UUID, rendant impossible la prédiction ou l'énumération des fichiers. Même si un attaquant connaît l'UUID d'un document, il ne peut y accéder sans un token d'authentification valide ou un lien de partage actif.

La validation des fichiers uploadés constitue une première ligne de défense. La taille maximale est limitée pour éviter les attaques par saturation. Le type MIME est vérifié en analysant le contenu réel du fichier, pas simplement son extension, offrant une protection contre les fichiers malveillants déguisés.

Pour les fichiers sensibles, il serait possible d'ajouter une couche de chiffrement supplémentaire, chiffrant les fichiers au repos avec une clé maîtresse. Cette fonctionnalité n'a pas été implémentée dans la version actuelle mais pourrait être ajoutée facilement grâce à l'abstraction du système de stockage Laravel.

### 7.4 Protection contre les attaques courantes

Plusieurs mécanismes protègent l'application contre les attaques web courantes.

**Protection CSRF :** Laravel intègre une protection native contre les attaques CSRF (Cross-Site Request Forgery) pour les formulaires web. Pour les API, cette protection n'est pas nécessaire car l'authentification par token Bearer ne peut pas être exploitée par une attaque CSRF classique.

**Protection XSS :** Les données retournées par l'API sont au format JSON brut, éliminant les risques d'injection de scripts. Le frontend est responsable d'échapper correctement les données avant de les afficher dans le DOM. Toutes les entrées utilisateur sont validées et sanitizées avant d'être stockées en base de données.

**Protection SQL Injection :** L'utilisation exclusive d'Eloquent ORM et de requêtes préparées élimine les risques d'injection SQL. Toutes les valeurs fournies par l'utilisateur sont automatiquement échappées et paramétrées, rendant impossible l'injection de code SQL malveillant.

**Rate Limiting :** Un système de limitation du nombre de requêtes par IP peut être configuré sur les endpoints sensibles comme la connexion et l'inscription, protégeant contre les attaques par force brute. Laravel fournit un middleware de throttling facilement configurable.

### 7.5 Système de journalisation et traçabilité

Le système de journalisation assure une traçabilité complète des opérations effectuées dans l'application, essentielle pour l'audit de sécurité et le diagnostic de problèmes.

Chaque action significative (création, modification, suppression de document, partage, etc.) déclenche un événement `UserActionLogged`. Cet événement est capturé par le listener `LogUserAction` qui enregistre de manière asynchrone les détails dans la table `log_actions`. Les informations enregistrées incluent le type d'action, la table affectée, l'utilisateur ayant effectué l'action, les valeurs avant et après modification au format JSON, l'adresse IP et le timestamp.

Un défi technique important a été la protection contre la récursion infinie. Si la création d'un log déclenchait elle-même un événement de journalisation, cela créerait une boucle infinie. Nous avons implémenté plusieurs mécanismes de protection : utilisation de `withoutEvents()` pour désactiver temporairement les événements lors de la création des logs, vérification du type de modèle pour ne jamais logger les logs eux-mêmes, et gestion explicite du dispatcher d'événements.

Les logs sont indexés sur les colonnes `created_at` et `(action, created_at)` pour optimiser les requêtes de recherche et de filtrage. Les administrateurs peuvent consulter les logs via des endpoints dédiés, filtrer par utilisateur, par action, par table ou par période, permettant ainsi un audit complet de l'activité du système.

---

## 8. Conclusion et perspectives

### 8.1 Bilan du projet

Le développement du backend YamsDrive a permis de créer une solution complète et robuste de gestion électronique de documents. L'application implémente l'ensemble des fonctionnalités essentielles : upload et stockage sécurisé des fichiers, système d'authentification moderne, partage flexible (ciblé, par service, par lien public), gestion des favoris, recherche avancée, statistiques détaillées, et traçabilité complète des actions.

L'approche itérative adoptée, avec quatre phases de développement distinctes, s'est avérée particulièrement efficace. Elle a permis de construire progressivement les fonctionnalités tout en maintenant un code stable et testable à chaque étape. Chaque phase apportait sa valeur ajoutée tout en s'appuyant solidement sur les fondations établies précédemment.

Les choix techniques effectués se sont révélés judicieux. Laravel 11 a fourni un framework solide et productif, Eloquent ORM a simplifié la gestion des données, Sanctum a offert une authentification sécurisée et moderne, et MySQL a garanti des performances et une fiabilité excellentes.

### 8.2 Compétences acquises

Ce projet m'a permis de développer et d'approfondir de nombreuses compétences techniques. J'ai acquis une maîtrise approfondie de Laravel et de son écosystème, incluant Eloquent ORM, le système de routing, la validation, les événements et listeners, et les migrations. J'ai appris à concevoir et implémenter des API RESTful respectant les standards et les bonnes pratiques. J'ai développé une compréhension solide des enjeux de sécurité web et des mécanismes de protection. J'ai pratiqué la modélisation de bases de données relationnelles complexes avec de nombreuses relations. J'ai mis en œuvre des techniques d'optimisation des performances via l'indexation et les requêtes efficaces.

Au-delà des aspects purement techniques, ce projet m'a également permis de développer des compétences méthodologiques : planification et découpage d'un projet en phases, documentation technique complète, et résolution de problèmes complexes comme la gestion de la récursion dans le système de logs.

### 8.3 Perspectives d'évolution

Plusieurs évolutions pourraient enrichir l'application dans le futur. Le versioning des documents permettrait de conserver un historique complet des modifications avec possibilité de restaurer des versions antérieures. Une prévisualisation avancée supportant les documents Office, les fichiers CAD et les vidéos améliorerait l'expérience utilisateur. L'intégration d'un scan antivirus automatique lors de l'upload renforcerait la sécurité. Le chiffrement des fichiers sensibles au repos offrirait une protection supplémentaire. Des notifications en temps réel via WebSockets informeraient instantanément les utilisateurs des partages et modifications. La compression automatique des fichiers optimiserait l'utilisation de l'espace disque. L'OCR pour l'indexation du contenu textuel des documents scannés faciliterait la recherche. Enfin, l'intégration avec des services cloud comme Amazon S3 ou Google Drive offrirait plus de flexibilité pour le stockage.

Ce projet constitue une base solide et évolutive sur laquelle ces améliorations pourraient être construites progressivement selon les besoins des utilisateurs.

---

**Mémoire rédigé dans le cadre du stage de fin d'études**  
**Projet : YamsDrive - Backend Laravel**  
**Période : Juin - Octobre 2025**
