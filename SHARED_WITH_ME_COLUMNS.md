# Colonnes retournées par l'endpoint "Partagé avec moi"

## Endpoint
```
GET /api/documents/shared-with-me
```

## Structure de la réponse

L'endpoint retourne un objet JSON avec une clé `data` contenant un tableau de documents partagés.

---

## 📋 Colonnes du Document

### Colonnes de base (table `documents`)

| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| `id` | integer | ID unique du document | `123` |
| `uuid` | string (UUID) | Identifiant unique universel | `"9c8e7f6d-5b4a-3c2d-1e0f-9a8b7c6d5e4f"` |
| `nom` | string | Nom du document | `"Contrat Client XYZ"` |
| `chemin` | string | Chemin de stockage du fichier | `"documents/2025/01/fichier.pdf"` |
| `type` | string | Type MIME du fichier | `"application/pdf"` |
| `taille` | integer | Taille du fichier en octets | `245678` |
| `description` | string/null | Description du document | `"Contrat signé le 15/01/2025"` |
| `user_id` | integer | ID du propriétaire du document | `5` |
| `service_id` | integer/null | ID du service associé | `3` |
| `created_at` | timestamp | Date de création | `"2025-01-15T10:30:00.000000Z"` |
| `updated_at` | timestamp | Date de dernière modification | `"2025-01-15T10:30:00.000000Z"` |
| `deleted_at` | timestamp/null | Date de suppression (soft delete) | `null` |

---

## 🔐 Colonnes ajoutées par le partage

Ces colonnes sont ajoutées dynamiquement par la méthode `sharedWithMe()` :

| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| `shared_by` | object | Informations sur l'utilisateur qui a partagé | `{"id": 5, "name": "Alice Dupont", "email": "alice@example.com"}` |
| `permission_level` | string | Niveau de permission (`read` ou `edit`) | `"read"` |
| `share_id` | integer | ID du partage (table `document_shares`) | `42` |
| `expires_at` | timestamp/null | Date d'expiration du partage | `"2025-12-31T23:59:59.000000Z"` ou `null` |

---

## 📦 Exemple de réponse complète

```json
{
  "data": [
    {
      "id": 123,
      "uuid": "9c8e7f6d-5b4a-3c2d-1e0f-9a8b7c6d5e4f",
      "nom": "Contrat Client XYZ",
      "chemin": "documents/2025/01/contrat_xyz.pdf",
      "type": "application/pdf",
      "taille": 245678,
      "description": "Contrat signé le 15/01/2025",
      "user_id": 5,
      "service_id": 3,
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-15T10:30:00.000000Z",
      "deleted_at": null,
      "shared_by": {
        "id": 5,
        "name": "Alice Dupont",
        "email": "alice@example.com"
      },
      "permission_level": "read",
      "share_id": 42,
      "expires_at": "2025-12-31T23:59:59.000000Z"
    },
    {
      "id": 124,
      "uuid": "8b7c6d5e-4f3a-2b1c-0d9e-8f7a6b5c4d3e",
      "nom": "Facture Janvier 2025",
      "chemin": "documents/2025/01/facture_jan.pdf",
      "type": "application/pdf",
      "taille": 125000,
      "description": null,
      "user_id": 7,
      "service_id": null,
      "created_at": "2025-01-20T14:00:00.000000Z",
      "updated_at": "2025-01-20T14:00:00.000000Z",
      "deleted_at": null,
      "shared_by": {
        "id": 7,
        "name": "Bob Martin",
        "email": "bob@example.com"
      },
      "permission_level": "edit",
      "share_id": 43,
      "expires_at": null
    }
  ]
}
```

---

## 🔍 Détails des colonnes ajoutées

### `shared_by` (objet)
Contient les informations de l'utilisateur qui a partagé le document :
- **`id`** : ID de l'utilisateur
- **`name`** : Nom complet de l'utilisateur
- **`email`** : Email de l'utilisateur

### `permission_level` (string)
Niveau de permission accordé :
- **`read`** : Lecture seule (consultation, téléchargement)
- **`edit`** : Lecture + modification

### `share_id` (integer)
ID unique du partage dans la table `document_shares`. Utile pour :
- Supprimer le partage
- Modifier les permissions
- Tracer l'historique

### `expires_at` (timestamp/null)
Date d'expiration du partage :
- **`null`** : Partage permanent (pas d'expiration)
- **timestamp** : Date et heure d'expiration (format ISO 8601)

---

## 📝 Notes importantes

1. **Filtrage automatique** : L'endpoint ne retourne que les partages **non expirés** (`expires_at` est `null` ou dans le futur).

2. **Authentification requise** : Un token Sanctum valide est obligatoire.

3. **Documents supprimés** : Les documents avec `deleted_at` non-null (corbeille) ne sont **pas inclus** par défaut.

4. **Relations chargées** :
   - `shared_by` : Informations de l'utilisateur qui a partagé
   - Le document complet avec toutes ses colonnes

5. **Permissions** : Le champ `permission_level` indique ce que l'utilisateur peut faire :
   - `read` : Voir, télécharger
   - `edit` : Voir, télécharger, modifier, supprimer

---

## 🚀 Utilisation côté front

### Afficher la liste des documents partagés
```javascript
async function getSharedDocuments() {
  const response = await fetch('/api/documents/shared-with-me', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  
  const { data } = await response.json();
  
  data.forEach(doc => {
    console.log(`Document: ${doc.nom}`);
    console.log(`Partagé par: ${doc.shared_by.name}`);
    console.log(`Permission: ${doc.permission_level}`);
    console.log(`Expire le: ${doc.expires_at || 'Jamais'}`);
  });
}
```

### Filtrer par permission
```javascript
// Documents en lecture seule
const readOnlyDocs = data.filter(doc => doc.permission_level === 'read');

// Documents modifiables
const editableDocs = data.filter(doc => doc.permission_level === 'edit');
```

### Vérifier l'expiration
```javascript
function isExpiringSoon(doc, daysThreshold = 7) {
  if (!doc.expires_at) return false;
  
  const expiryDate = new Date(doc.expires_at);
  const now = new Date();
  const daysUntilExpiry = (expiryDate - now) / (1000 * 60 * 60 * 24);
  
  return daysUntilExpiry > 0 && daysUntilExpiry <= daysThreshold;
}

// Documents expirant dans 7 jours
const expiringSoon = data.filter(doc => isExpiringSoon(doc, 7));
```

### Grouper par utilisateur qui a partagé
```javascript
const groupedBySharer = data.reduce((acc, doc) => {
  const sharerId = doc.shared_by.id;
  if (!acc[sharerId]) {
    acc[sharerId] = {
      user: doc.shared_by,
      documents: []
    };
  }
  acc[sharerId].documents.push(doc);
  return acc;
}, {});
```

---

## 🔗 Endpoints liés

- **`GET /api/documents/{uuid}/shares`** : Liste des partages d'un document spécifique
- **`POST /api/documents/{uuid}/share`** : Partager un document avec un utilisateur
- **`DELETE /api/documents/{uuid}/shares/{shareId}`** : Retirer un partage
- **`POST /api/documents/{uuid}/share-link`** : Générer un lien de partage public
