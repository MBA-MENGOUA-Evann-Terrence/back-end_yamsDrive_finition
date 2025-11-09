# Documentation - Recherche Globale

## 🎯 Objectif
Rechercher dans tous les documents accessibles à l'utilisateur (mes documents + partagés avec moi) depuis une barre de recherche unique, style Google Drive.

---

## 📍 Endpoint
```
GET /api/documents/search
```

**Contrôleur**: `DocumentSearchController@index`

---

## 🔐 Authentification
**Obligatoire** : Token Bearer (Sanctum)
```http
Authorization: Bearer <token>
Accept: application/json
```

---

## 🔍 Paramètres de recherche

### Recherche textuelle globale

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `q` | string | Recherche dans **nom du fichier**, **chemin**, **nom du service**, **nom du propriétaire** | `?q=contrat` |

### Filtres avancés

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `type` | string | Type MIME ou extension | `?type=application/pdf` ou `?type=pdf` |
| `extension` | string | Extension de fichier | `?extension=docx` |
| `service_id` | integer | ID du service | `?service_id=3` |
| `user_id` | integer | ID du propriétaire | `?user_id=5` |
| `date_from` | date | Date de création minimale (YYYY-MM-DD) | `?date_from=2025-01-01` |
| `date_to` | date | Date de création maximale (YYYY-MM-DD) | `?date_to=2025-12-31` |
| `taille_min` | integer | Taille minimale en octets | `?taille_min=1024` |
| `taille_max` | integer | Taille maximale en octets | `?taille_max=10485760` |
| `favoris` | boolean | Uniquement les favoris | `?favoris=true` |
| `corbeille` | boolean | Documents dans la corbeille | `?corbeille=true` |
| `shared_only` | boolean | Uniquement les documents partagés avec moi | `?shared_only=true` |

### Tri et pagination

| Paramètre | Type | Valeurs | Défaut | Description |
|-----------|------|---------|--------|-------------|
| `sort` | string | `nom`, `created_at`, `updated_at`, `taille` | `updated_at` | Champ de tri |
| `order` | string | `asc`, `desc` | `desc` | Ordre de tri |
| `per_page` | integer | 1-100 | 15 | Nombre de résultats par page |

---

## 📦 Format de réponse

### Structure paginée
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 123,
      "uuid": "abc-def-ghi",
      "nom": "Contrat Client XYZ",
      "file_name": "contrat_xyz.pdf",
      "type": "application/pdf",
      "taille": 245678,
      "created_at": "2025-01-15T10:30:00.000000Z",
      "updated_at": "2025-01-20T14:00:00.000000Z",
      "user_id": 5,
      "service_id": 3,
      "user": {
        "id": 5,
        "name": "Alice Dupont"
      },
      "service": {
        "id": 3,
        "nom": "Service Commercial"
      },
      "source": "my"
    },
    {
      "id": 124,
      "uuid": "def-ghi-jkl",
      "nom": "Rapport Q3",
      "file_name": "rapport_q3.pdf",
      "type": "application/pdf",
      "taille": 512000,
      "created_at": "2025-01-10T09:00:00.000000Z",
      "updated_at": "2025-01-22T16:30:00.000000Z",
      "user_id": 7,
      "service_id": 2,
      "user": {
        "id": 7,
        "name": "Bob Martin"
      },
      "service": {
        "id": 2,
        "nom": "Comptabilité"
      },
      "source": "shared"
    }
  ],
  "first_page_url": "http://localhost/api/documents/search?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "http://localhost/api/documents/search?page=5",
  "next_page_url": "http://localhost/api/documents/search?page=2",
  "path": "http://localhost/api/documents/search",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 73
}
```

### Champ `source`
- **`my`** : Document créé par l'utilisateur
- **`shared`** : Document partagé avec l'utilisateur

---

## 💡 Exemples d'utilisation

### 1. Recherche simple (barre globale)
```http
GET /api/documents/search?q=contrat
```
Cherche "contrat" dans : nom du fichier, chemin, nom du service, nom du propriétaire.

### 2. Recherche avec tri par date de modification
```http
GET /api/documents/search?q=rapport&sort=updated_at&order=desc
```

### 3. Recherche dans "Partagé avec moi" uniquement
```http
GET /api/documents/search?q=facture&shared_only=true
```

### 4. Recherche avancée combinée
```http
GET /api/documents/search?q=contrat&extension=pdf&service_id=3&date_from=2025-01-01&per_page=25
```

### 5. Recherche par service
```http
GET /api/documents/search?q=Comptabilité
```
Trouve tous les documents dont le service contient "Comptabilité".

### 6. Recherche par propriétaire
```http
GET /api/documents/search?q=Alice
```
Trouve tous les documents dont le propriétaire s'appelle "Alice".

---

## 🎨 Intégration Frontend

### Composant Vue 3 - Barre de recherche globale

```vue
<template>
  <div class="global-search">
    <input 
      v-model="searchQuery" 
      @input="handleSearch"
      placeholder="Rechercher dans tous vos documents..."
      class="search-input"
    />
    
    <div v-if="loading" class="loading">Recherche en cours...</div>
    
    <div v-if="results.length > 0" class="results">
      <div v-for="doc in results" :key="doc.id" class="result-item">
        <div class="doc-info">
          <span class="doc-name">{{ doc.nom }}</span>
          <span class="doc-service" v-if="doc.service">{{ doc.service.nom }}</span>
          <span class="doc-owner">{{ doc.user.name }}</span>
          <span class="badge" :class="doc.source">
            {{ doc.source === 'my' ? 'Mes documents' : 'Partagé avec moi' }}
          </span>
        </div>
        <div class="doc-meta">
          <span>{{ formatSize(doc.taille) }}</span>
          <span>{{ formatDate(doc.updated_at) }}</span>
        </div>
      </div>
    </div>
    
    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="pagination">
      <button @click="loadPage(pagination.current_page - 1)" :disabled="!pagination.prev_page_url">
        Précédent
      </button>
      <span>Page {{ pagination.current_page }} / {{ pagination.last_page }}</span>
      <button @click="loadPage(pagination.current_page + 1)" :disabled="!pagination.next_page_url">
        Suivant
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { debounce } from 'lodash';

const searchQuery = ref('');
const results = ref([]);
const loading = ref(false);
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  prev_page_url: null,
  next_page_url: null,
  total: 0
});

const searchDocuments = async (query, page = 1) => {
  if (!query.trim()) {
    results.value = [];
    return;
  }
  
  loading.value = true;
  
  try {
    const params = new URLSearchParams({
      q: query,
      sort: 'updated_at',
      order: 'desc',
      per_page: 20,
      page: page
    });
    
    const response = await fetch(`/api/documents/search?${params.toString()}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    });
    
    const data = await response.json();
    
    results.value = data.data;
    pagination.current_page = data.current_page;
    pagination.last_page = data.last_page;
    pagination.prev_page_url = data.prev_page_url;
    pagination.next_page_url = data.next_page_url;
    pagination.total = data.total;
    
  } catch (error) {
    console.error('Erreur de recherche:', error);
  } finally {
    loading.value = false;
  }
};

const handleSearch = debounce(() => {
  searchDocuments(searchQuery.value);
}, 300);

const loadPage = (page) => {
  searchDocuments(searchQuery.value, page);
};

const formatSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};
</script>

<style scoped>
.global-search {
  width: 100%;
  max-width: 800px;
  margin: 0 auto;
}

.search-input {
  width: 100%;
  padding: 12px 20px;
  font-size: 16px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  transition: border-color 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #4285f4;
}

.results {
  margin-top: 20px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
}

.result-item {
  padding: 16px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background-color 0.2s;
}

.result-item:hover {
  background-color: #f8f9fa;
}

.doc-info {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}

.doc-name {
  font-weight: 600;
  color: #202124;
}

.doc-service, .doc-owner {
  font-size: 14px;
  color: #5f6368;
}

.badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
}

.badge.my {
  background-color: #e8f0fe;
  color: #1967d2;
}

.badge.shared {
  background-color: #fce8e6;
  color: #d93025;
}

.doc-meta {
  display: flex;
  gap: 16px;
  font-size: 13px;
  color: #5f6368;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
}

.pagination button {
  padding: 8px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  background: white;
  cursor: pointer;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
```

### Fonction JavaScript réutilisable

```javascript
/**
 * Recherche globale de documents
 * @param {Object} params - Paramètres de recherche
 * @param {string} params.q - Terme de recherche
 * @param {boolean} params.shared_only - Uniquement partagés
 * @param {string} params.sort - Champ de tri
 * @param {string} params.order - Ordre (asc/desc)
 * @param {number} params.per_page - Résultats par page
 * @param {number} params.page - Numéro de page
 * @returns {Promise<Object>} Résultats paginés
 */
async function globalSearch({
  q = '',
  shared_only = false,
  sort = 'updated_at',
  order = 'desc',
  per_page = 15,
  page = 1,
  ...otherFilters
} = {}) {
  const params = new URLSearchParams({
    q,
    sort,
    order,
    per_page,
    page,
    ...(shared_only && { shared_only: true }),
    ...otherFilters
  });

  const response = await fetch(`/api/documents/search?${params.toString()}`, {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('token')}`,
      'Accept': 'application/json'
    }
  });

  if (!response.ok) {
    throw new Error(`Erreur de recherche: ${response.status}`);
  }

  return response.json();
}

// Exemples d'utilisation
const results1 = await globalSearch({ q: 'contrat' });
const results2 = await globalSearch({ q: 'facture', shared_only: true });
const results3 = await globalSearch({ q: 'rapport', extension: 'pdf', per_page: 25 });
```

---

## 🎯 Cas d'usage par contexte

### Barre de recherche globale (header)
```javascript
// Recherche dans tous les documents
GET /api/documents/search?q=<terme>&sort=updated_at&order=desc
```

### Recherche dans l'onglet "Partagé avec moi"
```javascript
// Restreindre aux documents partagés
GET /api/documents/search?q=<terme>&shared_only=true&sort=updated_at&order=desc
```

### Recherche dans l'onglet "Favoris"
```javascript
// Uniquement les favoris
GET /api/documents/search?q=<terme>&favoris=true&sort=updated_at&order=desc
```

### Recherche dans un service spécifique
```javascript
// Documents d'un service
GET /api/documents/search?q=<terme>&service_id=3&sort=updated_at&order=desc
```

---

## 📊 Performances et bonnes pratiques

### Côté front
1. **Debounce** : Attendre 300ms après la dernière frappe avant de lancer la recherche
2. **Pagination** : Charger 15-25 résultats par page
3. **Cache** : Mettre en cache les résultats récents (ex: avec React Query, Pinia)
4. **Loading state** : Afficher un indicateur pendant la recherche
5. **Empty state** : Message clair si aucun résultat

### Côté back
- Index sur `documents.nom`, `documents.updated_at`, `documents.user_id`
- Index sur `services.nom`, `users.name`
- Pagination obligatoire (max 100 par page)

---

## 🔗 Endpoints complémentaires

- **Liste "Partagé avec moi"** : `GET /api/documents/shared-with-me`
- **Liste "Récents"** : `GET /api/documents/recent`
- **Recherche "Récents"** : `GET /api/documents/recent/search`

---

## ✅ Résumé

- **Endpoint unique** : `GET /api/documents/search`
- **Recherche dans** : nom fichier, chemin, service, propriétaire
- **Tri par défaut** : `updated_at DESC` (dernière modification)
- **Champ `source`** : `my` ou `shared` pour l'affichage
- **Pagination** : 15 résultats par défaut
- **Filtres avancés** : type, service, dates, taille, favoris, corbeille
