# Mémoire Technique - Développement du Backend YamsDrive
## Partie 2 : Démarche de développement et Modélisation

---

## 4. Démarche de développement

Le développement de l'application a suivi une approche itérative et incrémentale, organisée en quatre phases distinctes. Cette méthodologie agile nous a permis de construire progressivement les fonctionnalités tout en maintenant un code stable et testable à chaque étape. Chaque phase s'appuyait sur les fondations établies lors de la phase précédente, permettant ainsi une évolution cohérente du projet.

### 4.1 Phase 1 : Mise en place des fondations (Juin-Juillet 2025)

La première phase du projet a consisté à établir les bases solides de l'application en créant les structures essentielles qui supporteraient toutes les fonctionnalités futures.

**Création de la structure de base de données :** Nous avons commencé par concevoir et implémenter les tables fondamentales du système. La table `services` a été créée en premier lieu pour permettre l'organisation des utilisateurs par départements ou équipes. Cette structuration organisationnelle facilite la gestion des permissions et le filtrage des données par unité. Chaque service possède un nom, une description et peut être associé à un tarif, offrant ainsi une flexibilité pour d'éventuelles fonctionnalités de facturation par service.

**Système d'authentification :** La table `users` a été mise en place avec l'ensemble des champs nécessaires pour gérer les profils utilisateurs complets. Au-delà des champs standards (nom, prénom, email), nous avons intégré des champs métier comme le rôle (pour différencier administrateurs et utilisateurs standards), le statut (pour activer ou désactiver des comptes), et les permissions (stockées au format JSON pour une flexibilité maximale). Nous avons également implémenté les mécanismes de gestion des sessions via la table `sessions` et de réinitialisation des mots de passe via la table `password_reset_tokens`, conformément aux bonnes pratiques de sécurité. Ces mécanismes garantissent que les utilisateurs peuvent récupérer l'accès à leur compte en cas d'oubli de mot de passe, tout en maintenant un niveau de sécurité élevé.

**Journalisation des actions :** Dès cette phase initiale, nous avons intégré un système de journalisation complet via la table `log_actions`. Cette décision stratégique permet de tracer toutes les opérations effectuées dans le système depuis le début du projet, offrant ainsi une traçabilité complète pour l'audit et le diagnostic. Chaque action enregistrée contient des informations détaillées : le type d'action, la table affectée, l'utilisateur ayant effectué l'action, les valeurs avant et après modification, l'adresse IP et le timestamp. Cette richesse d'information s'avère précieuse pour comprendre l'historique des modifications et identifier rapidement l'origine de problèmes éventuels.

### 4.2 Phase 2 : Gestion documentaire (Juillet-Août 2025)

La deuxième phase s'est concentrée sur le cœur fonctionnel de l'application : la gestion complète des documents.

**Création de l'entité Document :** Nous avons conçu la table `documents` avec l'ensemble des métadonnées nécessaires pour gérer efficacement les fichiers. Chaque document stocke son nom original, son chemin de stockage sur le serveur, son type MIME (permettant d'identifier le format du fichier), sa taille en octets (utile pour les statistiques et la gestion des quotas), et une description optionnelle que l'utilisateur peut renseigner pour contextualiser le document. Chaque document est associé à un propriétaire via une clé étrangère vers la table `users`, établissant ainsi clairement la responsabilité de chaque fichier. De plus, un document peut optionnellement être rattaché à un service, facilitant l'organisation et le filtrage des documents par unité organisationnelle.

**Implémentation des UUID :** Pour renforcer la sécurité et éviter l'exposition des identifiants séquentiels dans les URLs, nous avons ajouté un champ `uuid` unique pour chaque document. Les identifiants séquentiels (1, 2, 3...) présentent un risque de sécurité car ils permettent à un attaquant de deviner facilement les URLs d'autres documents. En utilisant des UUID (Universally Unique Identifiers) version 4, nous générons des identifiants aléatoires de 36 caractères qui sont statistiquement uniques et impossibles à prédire. Cette approche permet de générer des URLs stables et imprévisibles, rendant beaucoup plus difficile l'énumération des ressources par des utilisateurs malveillants. Les UUID sont utilisés dans toutes les URLs publiques, tandis que les identifiants séquentiels restent utilisés en interne pour les jointures de base de données, offrant ainsi le meilleur compromis entre sécurité et performance.

**Système de partage ciblé :** La table `document_shares` a été créée pour gérer le partage de documents entre utilisateurs internes de l'application. Cette table implémente une relation many-to-many enrichie entre documents et utilisateurs. Chaque partage enregistre non seulement le document partagé et l'utilisateur bénéficiaire, mais également l'auteur du partage (via le champ `shared_by`), permettant ainsi de tracer précisément qui a partagé quoi avec qui. Le niveau de permission peut être défini à "read" (lecture seule) ou "edit" (lecture et modification), offrant un contrôle granulaire sur les accès. Une date d'expiration optionnelle permet de créer des partages temporaires qui se révoquent automatiquement après une certaine période. Enfin, une contrainte d'unicité composite sur `(document_id, user_id)` garantit qu'un document ne peut être partagé qu'une seule fois avec un utilisateur donné, évitant ainsi les doublons et simplifiant la gestion des partages.

**Fonctionnalité des favoris :** Pour améliorer l'expérience utilisateur et faciliter l'accès aux documents importants, nous avons implémenté un système de favoris via la table `favoris`. Cette table de jonction établit une relation many-to-many entre utilisateurs et documents, permettant à chaque utilisateur de marquer autant de documents qu'il le souhaite comme favoris. Cette fonctionnalité permet aux utilisateurs d'accéder rapidement à leurs documents les plus consultés, sans avoir à naviguer dans l'arborescence complète ou effectuer des recherches répétées. Les contraintes de suppression en cascade garantissent que si un document ou un utilisateur est supprimé, les entrées correspondantes dans la table des favoris sont automatiquement nettoyées, maintenant ainsi l'intégrité des données.

### 4.3 Phase 3 : Optimisation et sécurité (Août 2025)

La troisième phase a été dédiée à l'amélioration des performances et au renforcement de la sécurité de l'application, deux aspects cruciaux pour une application destinée à gérer potentiellement des milliers de documents.

**Optimisation des performances :** Après une analyse approfondie des requêtes les plus fréquentes et de leurs temps d'exécution, nous avons identifié les opportunités d'optimisation. Des index stratégiques ont été ajoutés sur les colonnes `created_at` des tables `documents`, `document_shares` et `log_actions`. Ces index accélèrent considérablement les requêtes de tri et de filtrage par date, particulièrement importantes pour afficher les documents récents ou générer des statistiques temporelles. Un index composite `(action, created_at)` a également été créé sur la table des logs pour accélérer les requêtes combinant un filtrage par type d'action et par période. Ces optimisations ont permis de réduire les temps de réponse de plusieurs secondes à quelques millisecondes pour les requêtes complexes, améliorant significativement l'expérience utilisateur, particulièrement pour les fonctionnalités de statistiques et de recherche qui agrègent de grandes quantités de données.

**Amélioration du système de logs :** Les colonnes `nouvelles_valeurs` et `anciennes_valeurs` de la table `log_actions` ont été converties du type VARCHAR au type TEXT. Cette modification technique, bien que simple, s'est avérée cruciale. Le type VARCHAR est limité à 255 caractères (ou 65535 selon la configuration), ce qui pouvait entraîner une troncation des données JSON pour les objets volumineux. En passant au type TEXT, nous pouvons stocker jusqu'à 65535 caractères, garantissant ainsi l'intégrité complète des données de journalisation même pour les objets complexes contenant de nombreux champs. Cette amélioration assure que nous disposons toujours d'un historique complet et précis des modifications, essentiel pour l'audit et le diagnostic de problèmes.

**Implémentation des soft deletes :** Pour protéger les utilisateurs contre les suppressions accidentelles et permettre la récupération de données, nous avons ajouté le mécanisme de soft delete sur la table `documents`. Cette fonctionnalité ajoute une colonne `deleted_at` qui, lorsqu'elle contient une date, marque le document comme supprimé sans l'effacer physiquement de la base de données. Les documents supprimés sont automatiquement exclus des requêtes normales grâce au trait `SoftDeletes` d'Eloquent, mais restent accessibles via des requêtes spéciales. Les documents supprimés sont conceptuellement déplacés dans une "corbeille" d'où ils peuvent être restaurés en un clic. Cette approche offre une couche de sécurité supplémentaire particulièrement appréciée des utilisateurs, tout en facilitant la récupération de données en cas d'erreur humaine. De plus, les documents en corbeille peuvent être définitivement supprimés après une période définie, permettant ainsi un nettoyage progressif tout en maintenant une fenêtre de récupération.

### 4.4 Phase 4 : Fonctionnalités avancées (Septembre 2025)

La dernière phase a introduit des fonctionnalités avancées pour enrichir l'expérience utilisateur et étendre les possibilités de collaboration au-delà des frontières de l'organisation.

**Rattachement des utilisateurs aux services :** Nous avons ajouté une clé étrangère `service_id` sur la table `users`, permettant d'associer formellement chaque utilisateur à un service spécifique de l'organisation. Cette évolution structurelle facilite grandement le filtrage des données par service et ouvre la voie à des fonctionnalités de partage groupé. Par exemple, un utilisateur peut désormais partager un document avec tous les membres de son service en une seule opération, plutôt que de devoir sélectionner individuellement chaque destinataire. Cette fonctionnalité améliore significativement l'efficacité du partage dans les organisations structurées en départements ou équipes.

**Partage par lien public :** La table `share_links` a été créée pour permettre le partage de documents via des URLs publiques sécurisées. Cette fonctionnalité répond à un besoin fréquent : partager un document avec une personne externe ne possédant pas de compte dans l'application. Contrairement au partage ciblé qui nécessite que le destinataire soit un utilisateur enregistré, le partage par lien génère un token unique de 64 caractères permettant à n'importe qui possédant le lien d'accéder au document. Les liens peuvent avoir une date d'expiration configurable, permettant de créer des accès temporaires qui se révoquent automatiquement. Le niveau de permission (lecture ou édition) est également configurable, offrant ainsi un contrôle granulaire sur les accès externes. Cette fonctionnalité s'avère particulièrement utile pour partager des documents avec des clients, des partenaires ou des prestataires externes, sans nécessiter la création de comptes utilisateurs pour chacun d'eux.

Cette approche itérative et progressive nous a permis de construire une application robuste et évolutive, en validant chaque fonctionnalité avant de passer à la suivante. Chaque phase apportait sa valeur ajoutée tout en s'appuyant solidement sur les fondations établies précédemment.

---

## 5. Modélisation de la base de données

La conception de la base de données constitue un élément fondamental du projet, car elle détermine la structure sur laquelle repose l'ensemble de l'application. Nous avons opté pour un modèle relationnel normalisé qui garantit l'intégrité des données tout en optimisant les performances. Cette section détaille les entités principales, leurs attributs, et les relations qui les lient.

### 5.1 Entités principales et leurs attributs

#### 5.1.1 Entité Users (Utilisateurs)

La table `users` constitue le pilier central du système d'authentification et de gestion des utilisateurs. Elle stocke l'ensemble des informations nécessaires pour identifier, authentifier et gérer les utilisateurs de l'application.

Les attributs de cette table ont été soigneusement choisis pour répondre aux besoins métier. L'identifiant unique `id` sert de clé primaire et est auto-incrémenté par la base de données. Les champs `nom` et `prenom` permettent d'identifier clairement l'utilisateur dans l'interface, offrant une expérience plus personnalisée que l'utilisation d'un simple nom d'utilisateur. Le champ `email` sert d'identifiant de connexion unique, avec une contrainte d'unicité au niveau de la base de données empêchant la création de comptes en double. Le champ `password` stocke le mot de passe hashé avec l'algorithme bcrypt, garantissant qu'aucun mot de passe n'est jamais stocké en clair, même en cas de compromission de la base de données. Le champ `role` définit le rôle de l'utilisateur dans le système (administrateur, utilisateur standard, gestionnaire, etc.), permettant d'implémenter des contrôles d'accès basés sur les rôles. Le champ `statut` indique si le compte est actif ou désactivé, permettant de suspendre temporairement l'accès d'un utilisateur sans supprimer ses données. Enfin, le champ `service_id` établit le rattachement organisationnel de l'utilisateur à un service spécifique.

Cette structure complète permet une gestion fine des profils utilisateurs tout en maintenant la flexibilité nécessaire pour des évolutions futures, comme l'ajout de nouveaux rôles ou attributs.

#### 5.1.2 Entité Documents

La table `documents` représente le cœur métier de l'application, stockant les métadonnées de tous les fichiers gérés par le système.

L'identifiant `id` sert aux opérations internes et aux jointures de base de données, tandis que le champ `uuid` (UUID version 4) est utilisé dans les URLs publiques pour des raisons de sécurité. Le champ `nom` conserve le nom original du fichier tel que fourni par l'utilisateur lors de l'upload, permettant de présenter un nom familier dans l'interface. Le champ `chemin` stocke le chemin relatif du fichier dans le système de stockage, permettant de localiser le fichier physique. Le champ `type` contient le type MIME du fichier (application/pdf, image/jpeg, text/plain, etc.), information cruciale pour déterminer comment le fichier doit être traité et affiché. Le champ `taille` enregistre la taille du fichier en octets, utilisée pour les statistiques de stockage et la gestion des quotas. Le champ `description` est optionnel et permet à l'utilisateur d'ajouter des informations contextuelles sur le document. Les clés étrangères `user_id` et `service_id` établissent respectivement la propriété du document et son rattachement organisationnel. Enfin, le champ `deleted_at` implémente le soft delete, permettant de marquer un document comme supprimé sans l'effacer physiquement.

L'utilisation combinée d'un ID séquentiel et d'un UUID offre le meilleur des deux mondes : performances optimales pour les jointures internes grâce aux entiers, et sécurité renforcée pour les accès externes grâce aux UUID imprévisibles.

#### 5.1.3 Entité Services

La table `services` permet d'organiser les utilisateurs et les documents selon la structure organisationnelle de l'entreprise (départements, équipes, unités).

Chaque service possède un identifiant unique `id`, un nom descriptif `nom` (par exemple "Ressources Humaines", "Comptabilité", "Direction Technique"), une description détaillée `description` expliquant le périmètre et les responsabilités du service, et un champ optionnel `prix` permettant d'associer une tarification au service si nécessaire pour la facturation.

Cette structuration facilite la mise en place de politiques de sécurité et de partage basées sur l'appartenance organisationnelle, permettant par exemple de restreindre l'accès à certains documents aux seuls membres d'un service spécifique.

#### 5.1.4 Entité DocumentShares (Partages ciblés)

La table `document_shares` gère le partage de documents entre utilisateurs internes de l'application, implémentant une relation many-to-many enrichie entre documents et utilisateurs.

Chaque partage possède un identifiant unique `id`, une référence vers le document partagé via `document_id`, une référence vers l'utilisateur bénéficiaire via `user_id`, et une référence vers l'utilisateur ayant initié le partage via `shared_by`. Cette triple référence permet de tracer précisément qui a partagé quoi avec qui, information précieuse pour l'audit et la gestion des permissions. Le champ `permission_level` est une énumération définissant le niveau d'accès accordé : 'read' pour la lecture seule (consultation et téléchargement uniquement) ou 'edit' pour la lecture et la modification (possibilité de modifier les métadonnées du document). Le champ `token` stocke un token unique optionnel permettant d'accéder au partage sans authentification complète, utile pour certains cas d'usage spécifiques. Le champ `expires_at` permet de définir une date d'expiration du partage, après laquelle l'accès sera automatiquement révoqué.

Une contrainte d'unicité composite sur `(document_id, user_id)` garantit qu'un document ne peut être partagé qu'une seule fois avec un utilisateur donné, évitant ainsi les doublons et simplifiant la gestion des partages.

#### 5.1.5 Entité ShareLinks (Partages publics)

La table `share_links` implémente le partage de documents via des liens publics sécurisés, permettant de partager des documents avec des personnes ne possédant pas de compte dans l'application.

Chaque lien de partage possède un identifiant unique `id`, une référence vers le document partagé via `document_id`, un token aléatoire de 64 caractères via `token` garantissant l'unicité et la sécurité du lien, une référence vers l'utilisateur ayant créé le lien via `shared_by`, un niveau de permission via `permission_level`, et une date d'expiration optionnelle via `expires_at`.

Le token de 64 caractères est généré aléatoirement en utilisant des fonctions cryptographiquement sécurisées, rendant statistiquement impossible la prédiction ou la découverte de liens par force brute. Cette fonctionnalité permet de partager des documents avec des clients, des partenaires ou des prestataires externes, sans nécessiter la création de comptes utilisateurs pour chacun d'eux, tout en maintenant un contrôle sur les accès via les dates d'expiration et les niveaux de permission.
