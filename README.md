# 🏔️ ChezGigi — Planning de réservations (PHP/MySQL)

Application web pour gérer le planning de réservations d'une location saisonnière, avec synchronisation automatique des calendriers Airbnb et Booking.com.

## ✨ Fonctionnalités

- 📅 Calendrier mensuel (FullCalendar)
- 🔄 Synchronisation iCal Airbnb + Booking
- ➕ Réservations directes manuelles
- 🧹 Gestion des jours de ménage / entretien
- 📊 Stats par mois : occupation, nuits, revenus
- 🔐 Connexion par identifiant / mot de passe (sessions PHP)

## 📋 Prérequis

- PHP **8.0+** (avec PDO, curl ou allow_url_fopen)
- MySQL **5.7+** ou MariaDB **10.2+**
- phpMyAdmin (ou tout autre outil MySQL) pour créer la base
- Un serveur web (Apache, Nginx) avec mod_rewrite recommandé

## 🚀 Installation

### 1. Créer la base de données

Sur **phpMyAdmin** :

1. Crée une base nommée `chezgigi` (ou autre, à reporter dans `config.php`)
2. Importe le fichier `sql/schema.sql` (Importer → choisir le fichier)

### 2. Uploader les fichiers sur le serveur

Upload le **contenu du dossier `public/`** à la racine de ton domaine (ou sous-dossier).

⚠️ Le dossier `src/` doit aussi être uploadé, **mais en dehors du web root** si possible (un cran au-dessus de `public/`). La structure attendue sur le serveur est :

```
ton-serveur/
├── public_html/        ← contenu du dossier "public/" du repo
│   ├── index.php
│   ├── login.php
│   ├── settings.php
│   ├── api/
│   ├── assets/
│   └── config.php      ← à créer (voir étape 3)
└── src/                ← contenu du dossier "src/" du repo
    ├── db.php
    ├── auth.php
    └── ...
```

Si ton hébergement ne permet pas de mettre `src/` au-dessus de `public_html/`, mets-le **dans** `public_html/src/` et adapte les `require_once` (les chemins actuels supposent `../src/`).

### 3. Configurer

1. Copie `public/config.example.php` en `public/config.php`
2. Édite `config.php` avec tes credentials MySQL :
   ```php
   'db' => [
       'host'     => 'localhost',
       'name'     => 'chezgigi',
       'user'     => 'ton_user_mysql',
       'password' => 'ton_mot_de_passe',
   ],
   ```
3. Personnalise éventuellement les credentials de connexion (par défaut **gigi** / **tintin**).

### 4. Tester

Ouvre ton domaine dans un navigateur :
- Tu seras redirigé vers `login.php`
- Connecte-toi avec **gigi** / **tintin**
- Tu arrives sur le calendrier (vide)

### 5. Ajouter les sources iCal

Va dans **Paramètres** :

1. Clique sur ton annonce **Airbnb** → Calendrier → Disponibilité → Exporter le calendrier
2. Copie l'URL iCal et colle-la avec le nom "Airbnb"
3. Sur **Booking.com** → Extranet → Calendrier → Synchronisation → copie l'URL iCal
4. Ajoute-la avec le nom "Booking"
5. Clique sur **Synchroniser tout**

## 📂 Structure du projet

```
chezgigi-php/
├── public/                   # À uploader sur le web
│   ├── index.php             # Page calendrier
│   ├── login.php             # Connexion
│   ├── logout.php
│   ├── settings.php          # Gestion sources iCal
│   ├── config.example.php    # Template config (à copier)
│   ├── .htaccess             # Sécurité Apache
│   ├── api/
│   │   ├── reservations.php  # CRUD réservations
│   │   ├── ical-sources.php  # CRUD sources iCal
│   │   └── sync.php          # Synchronisation
│   └── assets/
│       ├── css/style.css
│       └── js/
│           ├── calendar.js
│           └── settings.js
├── src/                      # À mettre hors web root si possible
│   ├── db.php                # Connexion PDO
│   ├── auth.php              # Sessions
│   ├── helpers.php           # Helpers JSON, HTTP
│   └── ical-parser.php       # Parser iCal pur PHP
├── sql/
│   └── schema.sql            # Création des tables
└── README.md                 # Ce fichier
```

## 🔧 API

Toutes les routes nécessitent une session authentifiée (sinon `401`).

| Méthode | URL                                | Description                       |
|---------|------------------------------------|-----------------------------------|
| GET     | `/api/reservations.php`            | Liste des réservations            |
| POST    | `/api/reservations.php`            | Crée une réservation              |
| PUT     | `/api/reservations.php?id=X`       | Modifie la réservation X          |
| DELETE  | `/api/reservations.php?id=X`       | Supprime (uniquement si manuelle) |
| GET     | `/api/ical-sources.php`            | Liste des sources iCal            |
| POST    | `/api/ical-sources.php`            | Ajoute une source                 |
| DELETE  | `/api/ical-sources.php?id=X`       | Supprime la source                |
| POST    | `/api/sync.php`                    | Synchronise toutes les sources    |

## 🎨 Couleurs

- **Airbnb** : `#FF5A5F` (rouge corail)
- **Booking** : `#003580` (bleu marine)
- **Direct** : `#10B981` (vert)
- **Ménage** : `rgb(195, 198, 203)` (gris)
- **Bloqué (Airbnb Not available)** : `#D1D5DB` (gris clair)

## 🔐 Sécurité

- Les credentials BDD sont dans `config.php` (gitignoré)
- Le `.htaccess` bloque l'accès direct à `config.php`
- Les sessions sont en `httponly` + `secure` (sur HTTPS)
- Les requêtes SQL utilisent PDO avec prepared statements
- Le mot de passe app est en clair dans `config.php` — change-le pour quelque chose de fort

## 🐛 Dépannage

**Erreur "config.php manquant"**
→ Tu n'as pas copié `config.example.php` en `config.php`.

**Erreur de connexion MySQL**
→ Vérifie host, user, password, et que la base existe.

**La sync iCal ne marche pas**
→ Vérifie que ton serveur a `curl` ou `allow_url_fopen` activé.
→ Teste l'URL iCal directement dans un navigateur — elle doit retourner un fichier `.ics`.

**Mode debug**
→ Mets `'debug' => true` dans `config.php` pour voir les erreurs PHP.

## 📝 Licence

Usage personnel.
