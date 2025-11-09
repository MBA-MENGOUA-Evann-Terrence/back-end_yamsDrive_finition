# Documentation API - Recherche et Filtrage de Documents

## Endpoint
```
GET /api/documents/search
```

## Authentification
**Requis** : Token Bearer (Sanctum)
```
Authorization: Bearer {votre_token}
```

---

## Paramètres de Recherche

### 🔍 Recherche Textuelle

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `q` | string | Recherche dans le nom du document ET le nom du fichier | `?q=contrat` |

### 📁 Filtres par Type/Extension

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `type` | string | Type MIME complet ou extension | `?type=application/pdf` ou `?type=pdf` |
| `extension` | string | Extension de fichier (pdf, docx, jpg, etc.) | `?extension=pdf` |

### 👤 Filtres par Utilisateur/Service

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `user_id` | integer | ID de l'utilisateur propriétaire | `?user_id=5` |
| `service_id` | integer | ID du service associé | `?service_id=3` |

### 📅 Filtres par Date

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `date_from` | date | Date de création minimale (YYYY-MM-DD) | `?date_from=2025-01-01` |
| `date_to` | date | Date de création maximale (YYYY-MM-DD) | `?date_to=2025-12-31` |

### 📏 Filtres par Taille

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `taille_min` | integer | Taille minimale en octets | `?taille_min=1024` (1 KB) |
| `taille_max` | integer | Taille maximale en octets | `?taille_max=10485760` (10 MB) |

### ⭐ Filtres Spéciaux

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `favoris` | boolean | Uniquement les documents favoris | `?favoris=true` ou `?favoris=1` |
| `corbeille` | boolean | `true` = corbeille, `false` = actifs, absent = actifs par défaut | `?corbeille=true` |
| `shared_only` | boolean | Uniquement les documents partagés avec moi | `?shared_only=true` |

### 🔄 Tri et Pagination

| Paramètre | Type | Valeurs possibles | Défaut | Exemple |
|-----------|------|-------------------|--------|---------|
| `sort` | string | `nom`, `created_at`, `updated_at`, `taille` | `created_at` | `?sort=taille` |
| `order` | string | `asc`, `desc` | `desc` | `?order=asc` |
| `per_page` | integer | 1-100 | 15 | `?per_page=25` |

---

## Exemples d'Utilisation

### 1. Recherche simple par mot-clé
```bash
GET /api/documents/search?q=facture
```

### 2. Recherche de PDFs créés en janvier 2025
```bash
GET /api/documents/search?extension=pdf&date_from=2025-01-01&date_to=2025-01-31
```

### 3. Documents favoris triés par taille
```bash
GET /api/documents/search?favoris=true&sort=taille&order=desc
```

### 4. Documents partagés avec moi (uniquement)
```bash
GET /api/documents/search?shared_only=true
```

### 5. Recherche avancée combinée
```bash
GET /api/documents/search?q=contrat&extension=pdf&taille_min=10240&taille_max=5242880&sort=created_at&order=desc&per_page=20
```
*Recherche "contrat" dans les PDFs entre 10 KB et 5 MB, triés par date décroissante, 20 résultats par page*

### 6. Documents dans la corbeille
```bash
GET /api/documents/search?corbeille=true
```

### 7. Documents d'un service spécifique
```bash
GET /api/documents/search?service_id=3&sort=nom&order=asc
```

---

## Réponse

### Structure de la réponse (paginée)
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
      "updated_at": "2025-01-15T10:30:00.000000Z",
      "user": {
        "id": 5,
        "name": "Alice Dupont"
      },
      "service": {
        "id": 3,
        "nom": "Service Commercial"
      }
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

---

## Exemple d'Intégration Frontend (JavaScript/Vue/React)

### Fonction de recherche réutilisable
```javascript
async function searchDocuments(filters = {}) {
  const params = new URLSearchParams();
  
  // Ajouter les filtres non vides
  Object.keys(filters).forEach(key => {
    if (filters[key] !== null && filters[key] !== undefined && filters[key] !== '') {
      params.append(key, filters[key]);
    }
  });
  
  const response = await fetch(`/api/documents/search?${params.toString()}`, {
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('token')}`,
      'Accept': 'application/json'
    }
  });
  
  return await response.json();
}

// Exemples d'utilisation
const results1 = await searchDocuments({ q: 'facture', extension: 'pdf' });
const results2 = await searchDocuments({ favoris: true, sort: 'created_at', order: 'desc' });
const results3 = await searchDocuments({ shared_only: true, per_page: 25 });
```

### Composant de barre de recherche (Vue 3)
```vue
<template>
  <div class="search-bar">
    <input 
      v-model="searchQuery" 
      @input="handleSearch"
      placeholder="Rechercher un document..."
      class="search-input"
    />
    
    <div class="filters">
      <select v-model="filters.extension" @change="handleSearch">
        <option value="">Tous les types</option>
        <option value="pdf">PDF</option>
        <option value="docx">Word</option>
        <option value="xlsx">Excel</option>
        <option value="jpg">Image</option>
      </select>
      
      <label>
        <input type="checkbox" v-model="filters.favoris" @change="handleSearch" />
        Favoris uniquement
      </label>
      
      <label>
        <input type="checkbox" v-model="filters.shared_only" @change="handleSearch" />
        Partagés avec moi
      </label>
      
      <select v-model="filters.sort" @change="handleSearch">
        <option value="created_at">Date de création</option>
        <option value="nom">Nom</option>
        <option value="taille">Taille</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { debounce } from 'lodash'; // ou votre propre fonction debounce

const searchQuery = ref('');
const filters = reactive({
  extension: '',
  favoris: false,
  shared_only: false,
  sort: 'created_at',
  order: 'desc',
  per_page: 20
});

const handleSearch = debounce(async () => {
  const params = {
    q: searchQuery.value,
    ...filters
  };
  
  const results = await searchDocuments(params);
  // Traiter les résultats...
}, 300);
</script>
```

---

## Notes Importantes

1. **Authentification obligatoire** : Tous les appels nécessitent un token Sanctum valide
2. **Pagination** : Les résultats sont paginés par défaut (15 par page)
3. **Performance** : Utilisez `debounce` pour la recherche textuelle en temps réel
4. **Combinaison de filtres** : Tous les filtres peuvent être combinés
5. **Soft deletes** : Par défaut, seuls les documents actifs sont retournés (sauf si `corbeille=true`)
6. **Permissions** : L'utilisateur ne voit que ses documents + ceux partagés avec lui

---

## Codes d'Erreur

| Code | Description |
|------|-------------|
| 200 | Succès |
| 401 | Non authentifié (token manquant ou invalide) |
| 422 | Validation échouée (paramètres invalides) |
| 500 | Erreur serveur |
