# Manga Collection PWA — Fil conducteur du projet

## 0. Vision du projet

Créer une application web/PWA de gestion de collections de mangas, inspirée des usages de MangaCollec mais avec une identité, une UX et des fonctionnalités propres.

### Objectifs principaux

- Gérer sa collection de mangas et de tomes.
- Permettre à plusieurs utilisateurs de partager une collection.
- Distinguer les mangas/tomes possédés par chaque membre.
- Suivre les tomes lus, possédés, souhaités ou manquants.
- Rechercher et importer des mangas depuis des sources externes.
- Être installable comme PWA sur ordinateur et mobile.
- Fonctionner correctement sur mobile.
- Garder les données de collection dans sa propre base Laravel.
- Éviter de dépendre directement d'une API externe à chaque affichage.

### Positionnement portfolio

Le projet doit montrer :

- Laravel moderne et API REST.
- Architecture propre et relations Eloquent.
- Authentification et autorisation.
- Vue.js + Tailwind CSS.
- PWA / Service Worker.
- Jobs, queues et tâches planifiées.
- Intégration d'APIs externes.
- Gestion collaborative.
- Recherche et filtrage.
- UX mobile-first.
- Tests automatisés.
- Docker / CI si pertinent.

---

# 1. Stack technique

## Backend

- Laravel
- PHP
- MySQL ou MariaDB
- Laravel Sanctum
- Eloquent
- Laravel Queues
- Laravel Scheduler
- éventuellement Redis

## Frontend

- Vue.js
- Vite
- Tailwind CSS
- éventuellement Pinia

## PWA

- Web App Manifest
- Service Worker
- Cache des assets
- Stratégies offline ciblées
- Installation mobile / desktop

## Développement

- Git
- GitHub ou GitLab
- Docker
- PHPUnit / Pest
- Laravel Pint
- éventuellement PHPStan / Larastan

---

# 2. Architecture générale

```text
                 Sources externes
          ┌──────────┬──────────────┐
          │          │              │
        Jikan   Open Library   Google Books
          │          │              │
          └──────────┼──────────────┘
                     ▼
              Laravel Importer
                     │
              Jobs / Queues
                     │
                     ▼
              MySQL / MariaDB
                     │
              Laravel API
                     │
                     ▼
              Vue.js / PWA
                     │
              ┌──────┴──────┐
              │             │
           Desktop        Mobile
```

## Principe important

Les APIs externes servent principalement à **alimenter et enrichir la base locale**.

L'application ne doit pas dépendre d'un appel externe à chaque consultation d'un manga.

---

# 3. Phase 1 — Initialisation du projet

## Étape 1 — Créer le projet Laravel

- Créer le projet Laravel.
- Configurer `.env`.
- Configurer MySQL/MariaDB.
- Vérifier les migrations.
- Configurer Git.
- Créer le repository distant.

### Checklist

- [ ] Projet Laravel fonctionnel
- [ ] Base de données connectée
- [ ] Migration fonctionnelle
- [ ] Repository Git créé
- [ ] `.env.example` propre
- [ ] README initial

---

# 4. Phase 2 — Définir le modèle métier

Avant de développer l'interface, définir précisément les données.

## Entités principales

### User

Utilisateur de l'application.

```text
users
- id
- name
- email
- password
- avatar
- created_at
- updated_at
```

### Manga

La série manga indépendamment de son édition française.

```text
mangas
- id
- external_id
- title
- original_title
- synopsis
- author
- status
- total_volumes
- cover_url
- created_at
- updated_at
```

`external_id` peut contenir par exemple l'identifiant provenant de Jikan/MAL.

### Edition

Une même série peut avoir plusieurs éditions.

```text
editions
- id
- manga_id
- publisher
- language
- collection_name
- format
- created_at
- updated_at
```

Exemples :

```text
One Piece
├── Glénat - édition française
├── Edition japonaise
└── Edition anglaise
```

### Volume

Le tome physique.

```text
volumes
- id
- edition_id
- number
- title
- isbn10
- isbn13
- publication_date
- page_count
- cover_url
- created_at
- updated_at
```

### Collection

Une collection peut être personnelle ou partagée.

```text
collections
- id
- name
- owner_id
- type
- created_at
- updated_at
```

Types possibles :

```text
personal
shared
```

### Collection Member

Utilisateurs ayant accès à une collection.

```text
collection_members
- id
- collection_id
- user_id
- role
- created_at
- updated_at
```

Rôles possibles :

```text
owner
editor
viewer
```

### Collection Item

Lien entre un volume et une collection.

```text
collection_items
- id
- collection_id
- volume_id
- user_id
- status
- purchase_price
- purchased_at
- read_at
- notes
- created_at
- updated_at
```

Le `user_id` permet de savoir qui possède le tome dans une collection partagée.

---

# 5. Relations Eloquent

Objectif :

```text
User
 ├── Collections
 └── CollectionMemberships

Collection
 ├── Owner
 ├── Members
 └── Items

Manga
 ├── Editions
 └── ...

Edition
 ├── Manga
 └── Volumes

Volume
 ├── Edition
 └── CollectionItems

CollectionItem
 ├── Collection
 ├── Volume
 └── User
```

Créer ensuite :

- Models
- Factories
- Seeders
- Migrations
- Policies

---

# 6. Phase 3 — Authentification

Mettre en place :

- Inscription
- Connexion
- Déconnexion
- Mot de passe oublié
- Vérification email si nécessaire
- Profil utilisateur

## API

Utiliser Laravel Sanctum pour l'authentification de la SPA/PWA.

Prévoir :

```text
/api/auth/register
/api/auth/login
/api/auth/logout
/api/auth/me
```

### Checklist

- [ ] Register
- [ ] Login
- [ ] Logout
- [ ] User connecté
- [ ] Middleware auth
- [ ] Sanctum
- [ ] Tests d'authentification

---

# 7. Phase 4 — Sources de données manga

## Source principale envisagée : Jikan

Utiliser Jikan pour récupérer les informations générales :

- titre
- titres alternatifs
- synopsis
- auteur
- genres
- statut
- nombre de tomes
- couverture
- identifiant externe

Documentation :

https://docs.jikan.moe/

## Sources d'éditions

Tester :

- Open Library
- Google Books

Objectif :

- ISBN
- éditeur
- date de publication
- nombre de pages
- couverture
- informations d'édition

### Important

Ne pas supposer que les APIs possèdent toutes les données des éditions françaises.

Faire des tests réels avec plusieurs séries françaises.

---

# 8. Phase 5 — Créer un système d'import

Créer une couche dédiée aux sources externes.

Exemple :

```text
app/
└── Services/
    └── Manga/
        ├── JikanService.php
        ├── OpenLibraryService.php
        ├── GoogleBooksService.php
        └── MangaImporter.php
```

## Objectif

Ne jamais mettre directement les appels HTTP dans les Controllers.

Exemple logique :

```text
Controller
    ↓
MangaImporter
    ↓
JikanService
    ↓
API externe
    ↓
Mapper
    ↓
Model Laravel
```

---

# 9. Phase 6 — Synchronisation avec les APIs

Créer des Jobs Laravel.

Exemples :

```text
ImportMangaJob
ImportVolumesJob
UpdateMangaJob
UpdateVolumeJob
```

Utiliser les queues pour éviter de bloquer les requêtes utilisateur.

Exemple :

```text
Utilisateur ajoute One Piece
        ↓
Laravel crée l'import
        ↓
Job envoyé en queue
        ↓
API externe
        ↓
Données enregistrées
        ↓
Notification / résultat
```

## Scheduler

Prévoir éventuellement :

```text
mise à jour quotidienne
        ↓
mangas suivis
        ↓
nouveaux tomes / informations
```

---

# 10. Phase 7 — Recherche de mangas

Créer une recherche globale.

```text
GET /api/mangas/search?q=one+piece
```

Le système peut chercher :

1. Dans la BDD locale.
2. Si aucun résultat pertinent :
   rechercher via API externe.
3. Proposer l'import.
4. Enregistrer le manga.

Interface :

```text
┌──────────────────────────────┐
│ 🔍 Rechercher un manga       │
│ One Piece________________    │
├──────────────────────────────┤
│ 📕 One Piece                 │
│ Eiichiro Oda                 │
│ 114 tomes                    │
│                    Ajouter + │
└──────────────────────────────┘
```

---

# 11. Phase 8 — Gestion des collections

Créer les fonctionnalités principales :

## Collection personnelle

```text
Ma collection
├── One Piece
│   ├── Tome 1 ✓
│   ├── Tome 2 ✓
│   └── Tome 3 ✗
├── Berserk
└── Frieren
```

## Collection partagée

Exemple :

```text
Notre Mangathèque

Nicolas
├── One Piece #1
├── One Piece #2
└── Berserk #1

Chloé
├── Naruto #1
└── Frieren #1

Commun
├── One Piece #3
└── JJK #1
```

---

# 12. Phase 9 — Statuts des tomes

Définir clairement les statuts.

Exemple :

```text
owned
wanted
reading
read
missing
lent
```

Attention à ne pas multiplier inutilement les statuts.

Une approche possible :

```text
ownership_status
- owned
- wanted
- missing
- lent
```

Et une propriété séparée :

```text
read_at
```

Cela évite de mélanger possession et lecture.

---

# 13. Phase 10 — Dashboard

Créer un dashboard utilisateur.

Exemple :

```text
┌───────────────────────────────────┐
│ Notre Mangathèque                 │
│                                   │
│ 1 247 tomes       186 séries      │
│                                   │
│  👤 Nicolas      642              │
│  👩 Chloé        531              │
│  👥 Commun       74               │
│                                   │
├───────────────────────────────────┤
│ Derniers ajouts                   │
│                                   │
│ One Piece #112        Nicolas     │
│ JJK #30              Chloé        │
│ Frieren #15          Commun       │
└───────────────────────────────────┘
```

Statistiques possibles :

- Nombre de séries
- Nombre de tomes
- Tomes lus
- Tomes manquants
- Tomes souhaités
- Budget dépensé
- Progression par série
- Derniers achats

---

# 14. Phase 11 — Fiche manga

Créer une fiche complète.

```text
One Piece

[Cover]

Eiichiro Oda

Statut : En cours
114 tomes

Synopsis...

Progression
██████████░░░░ 82%

Tomes

✓ 01
✓ 02
✓ 03
✓ 04
✗ 05
✗ 06
...
```

Ajouter :

- informations générales
- éditions
- liste des volumes
- progression
- collection
- wishlist
- informations ISBN

---

# 15. Phase 12 — Scanner ISBN

Fonctionnalité bonus très intéressante pour une PWA.

Flux :

```text
Scanner
   ↓
Code-barres
   ↓
ISBN
   ↓
Recherche BDD
   ↓
Open Library
   ↓
Google Books
   ↓
Manga / Volume trouvé
   ↓
Ajouter à la collection
```

Interface mobile :

```text
┌─────────────────────────┐
│                         │
│       ┌─────────┐       │
│       │         │       │
│       │ SCANNER │       │
│       │         │       │
│       └─────────┘       │
│                         │
│   Placez le code-barres │
│   dans le cadre         │
└─────────────────────────┘
```

Cette fonctionnalité peut devenir un des points forts du projet.

---

# 16. Phase 13 — Partage et collaboration

Créer :

- invitation par email ou lien
- ajout d'un membre
- suppression d'un membre
- rôles
- modification des permissions

Exemple :

```text
Notre Mangathèque

Nicolas
Owner

Chloé
Editor

[ Inviter quelqu'un ]
```

## Sécurité

Créer des Policies Laravel :

```text
CollectionPolicy
CollectionItemPolicy
```

Vérifier systématiquement que l'utilisateur possède les droits nécessaires.

---

# 17. Phase 14 — PWA

Mettre en place :

## Manifest

```text
name
short_name
icons
theme_color
background_color
display: standalone
```

## Service Worker

Mettre en cache :

- CSS
- JS
- images nécessaires
- pages publiques
- données utiles

## Offline

La collection doit rester consultable même avec une connexion faible.

Objectif :

```text
Internet
   ↓
Synchronisation
   ↓
Cache local
   ↓
Consultation offline
```

Pour une V1, ne pas chercher à rendre toute l'application offline.

Commencer par :

- consultation de collection
- consultation des fiches
- navigation de base

Puis ajouter la synchronisation offline si nécessaire.

---

# 18. Phase 15 — UX / Responsive

Approche **mobile-first**.

Priorités :

### Mobile

- navigation simple
- boutons accessibles au pouce
- scanner rapidement
- ajout d'un tome en quelques secondes

### Desktop

- dashboard plus riche
- tableaux
- filtres
- statistiques
- gestion de collection

Prévoir :

```text
Mobile
   ↓
Bottom navigation

Desktop
   ↓
Sidebar
```

---

# 19. Phase 16 — Recherche et filtres

Ajouter :

- recherche par titre
- auteur
- éditeur
- ISBN
- genre

Filtres collection :

```text
Tous
Possédés
Manquants
Souhaités
Lus
Non lus
```

Tri :

```text
Titre
Auteur
Date
Dernier ajout
Numéro de tome
```

---

# 20. Phase 17 — API REST

Prévoir une API structurée.

Exemple :

```text
/api/mangas
/api/mangas/{manga}

/api/editions
/api/editions/{edition}

/api/volumes
/api/volumes/{volume}

/api/collections
/api/collections/{collection}

/api/collections/{collection}/items

/api/users/me
```

Utiliser API Resources Laravel pour normaliser les réponses.

---

# 21. Phase 18 — Tests

Tester en priorité :

## Unit tests

- services d'import
- parsers
- calculs statistiques

## Feature tests

- authentification
- création de collection
- ajout d'un volume
- suppression
- permissions
- partage
- API

Exemple :

```text
Un utilisateur ne peut pas modifier
une collection à laquelle il n'a qu'un accès viewer.
```

---

# 22. Phase 19 — Performance

Prévoir :

- eager loading Eloquent
- pagination
- cache Laravel
- queues
- optimisation des images
- index SQL
- rate limiting API
- cache des réponses externes

Éviter les problèmes N+1.

---

# 23. Phase 20 — Sécurité

Checklist :

- [ ] Validation Form Request
- [ ] Policies
- [ ] Sanctum
- [ ] Rate limiting
- [ ] Protection CSRF si nécessaire
- [ ] Mass assignment correctement configuré
- [ ] Validation des URLs/images externes
- [ ] Secrets uniquement dans `.env`
- [ ] Permissions vérifiées côté backend
- [ ] Aucun accès direct aux collections privées

---

# 24. Phase 21 — Docker

Créer une stack de développement :

```text
Docker
├── PHP / Laravel
├── MySQL
├── Redis
└── Node
```

Objectif :

```bash
docker compose up -d
```

et obtenir immédiatement un environnement fonctionnel.

---

# 25. Phase 22 — CI/CD

Ajouter GitHub Actions ou GitLab CI.

Pipeline :

```text
Push
 ↓
Install dependencies
 ↓
Lint
 ↓
PHPStan
 ↓
Tests
 ↓
Build frontend
 ↓
Deploy
```

---

# 26. Phase 23 — Déploiement

Pour une première version :

- serveur Linux / VPS
- Nginx
- PHP-FPM
- MySQL
- Redis
- Supervisor pour les queues
- HTTPS
- cron Laravel Scheduler

Architecture :

```text
Internet
   ↓
Nginx
   ↓
Laravel
   ├── PHP-FPM
   ├── MySQL
   └── Redis
          ↓
        Queue
```

---

# 27. Roadmap MVP

Ne pas développer toutes les fonctionnalités immédiatement.

## MVP 1 — Base

- [ ] Laravel
- [ ] MySQL
- [ ] Auth
- [ ] Modèle Manga
- [ ] Modèle Volume
- [ ] Collection personnelle
- [ ] Ajouter/supprimer un volume
- [ ] Recherche manga
- [ ] Import Jikan

## MVP 2 — Collection complète

- [ ] Editions
- [ ] ISBN
- [ ] Open Library
- [ ] Google Books fallback
- [ ] Statuts
- [ ] Recherche avancée
- [ ] Dashboard
- [ ] Fiche manga

## MVP 3 — Collaboration

- [ ] Collections partagées
- [ ] Invitations
- [ ] Membres
- [ ] Rôles
- [ ] Permissions
- [ ] Possesseur d'un tome

## MVP 4 — PWA

- [ ] Manifest
- [ ] Installation
- [ ] Service Worker
- [ ] Cache
- [ ] Responsive mobile
- [ ] Offline partiel

## MVP 5 — Features avancées

- [ ] Scanner ISBN
- [ ] Statistiques avancées
- [ ] Historique
- [ ] Notifications
- [ ] Suivi des sorties
- [ ] Wishlist
- [ ] Import/export de collection

---

# 28. Ordre de développement recommandé

```text
1. Initialisation Laravel
        ↓
2. Base de données
        ↓
3. Authentification
        ↓
4. Manga / Edition / Volume
        ↓
5. Collection
        ↓
6. Import Jikan
        ↓
7. Import ISBN / éditions
        ↓
8. Interface Vue
        ↓
9. Dashboard
        ↓
10. Partage de collection
        ↓
11. Permissions
        ↓
12. PWA
        ↓
13. Scanner ISBN
        ↓
14. Tests
        ↓
15. Docker / CI
        ↓
16. Déploiement
```

---

# 29. Règles d'architecture à respecter

## 1. Ne pas mélanger Manga et Volume

Une série et une édition physique sont deux choses différentes.

## 2. Ne pas dépendre directement des APIs externes

Les données utiles doivent être stockées localement.

## 3. Pas d'appels API dans les Controllers

Utiliser des Services / Actions dédiés.

## 4. Utiliser les Jobs pour les imports lourds

Ne pas bloquer une requête HTTP pendant un import complexe.

## 5. Autorisation côté backend

Le frontend ne doit jamais être considéré comme une couche de sécurité.

## 6. Mobile-first

La PWA doit être pensée pour le téléphone dès le départ.

## 7. Construire le MVP avant les features avancées

Éviter de commencer directement par le scanner, les statistiques ou l'offline complet.

---

# 30. Fonctionnalités bonus

Une fois le projet stable :

- Scanner plusieurs mangas à la suite.
- Import CSV.
- Export CSV/JSON.
- Historique des achats.
- Prix total de la collection.
- Valeur estimée de la collection.
- Notifications de sorties.
- Calendrier des prochaines sorties.
- Wishlist.
- Recommandations.
- Liste des tomes manquants.
- Détection des doublons.
- Gestion des prêts.
- Emplacement physique du manga.
- Photos personnelles des collections.
- Mode sombre.
- Synchronisation offline avancée.
- Statistiques par éditeur/auteur/genre.
- Partage public d'une collection.

---

# 31. Première milestone concrète

Le premier objectif ne doit pas être de créer toute l'application.

### Milestone #1

Créer :

```text
Laravel
   +
MySQL
   +
User
   +
Manga
   +
Edition
   +
Volume
   +
Collection
   +
CollectionItem
```

Puis réussir ce scénario de bout en bout :

```text
Nicolas crée un compte
        ↓
Recherche "One Piece"
        ↓
Laravel interroge Jikan
        ↓
One Piece apparaît
        ↓
Nicolas importe le manga
        ↓
Les volumes sont enregistrés
        ↓
Nicolas crée sa collection
        ↓
Il ajoute One Piece #1
        ↓
Le tome apparaît dans sa collection
```

**Une fois ce scénario fonctionnel, le reste du projet devient une succession de fonctionnalités autour de cette base.**

---

# 32. Vision finale

L'application finale doit permettre de passer de :

```text
"Je possède des mangas"
```

à :

```text
"Je gère toute ma collection manga,
je sais exactement ce que je possède,
ce qui me manque, ce que j'ai lu,
ce que possède ma copine,
et ce que nous avons en commun."
```

Le projet doit rester une **application originale inspirée des besoins identifiés**, et non une copie visuelle ou technique de MangaCollec.
