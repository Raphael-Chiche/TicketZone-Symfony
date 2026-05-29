# TicketZone - Plateforme de Gestion d'Événements

## 👥 Équipe

- **Raphaël CHICHE**
- **Léo RICHÉ**

---

## 📋 Table des matières

1. [Installation](#-installation)
2. [Commandes CLI](#-commandes-cli)
3. [Worker Messenger](#-worker-messenger)
4. [Configuration .env.local](#-configuration-envlocal)

---

## 🚀 Installation

Suivez cette procédure complète pour mettre en place le projet localement.

### 1️⃣ Cloner le dépôt

```bash
git clone <URL_DU_DEPOT>
cd TicketZone-Symfony
```

### 2️⃣ Installer les dépendances Composer

```bash
composer install
```

Cette commande installe toutes les dépendances PHP définies dans `composer.json` et génère l'autoloader.

### 3️⃣ Configurer la base de données

#### 3.1 - Créer le fichier `.env.local`

Créez un fichier `.env.local` à la racine du projet avec vos variables d'environnement locales (voir section [Configuration .env.local](#-configuration-envlocal)).

#### 3.2 - Démarrer la base de données (Docker)

Si vous utilisez Docker Compose :

```bash
docker-compose up -d
```

Cela lance le service PostgreSQL défini dans `compose.yaml`.

#### 3.3 - Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 4️⃣ Exécuter les migrations Doctrine

```bash
php bin/console doctrine:migrations:migrate
```

Cette commande applique toutes les migrations SQL pour créer la structure des tables.

### 5️⃣ Charger les fixtures (données de test)

```bash
php bin/console doctrine:fixtures:load
```

Cela remplit la base de données avec des données de test :
- **Admin** : `admin@ticketzone.test` / `password123`
- **Client** : `client@ticketzone.test` / `password123`
- **Événements** : Plusieurs événements (passés, à venir)
- **Réservations** : Exemples de réservations avec différents statuts

### 6️⃣ Lancer le serveur de développement

```bash
php bin/console server:run
```

Ou avec Symfony CLI :

```bash
symfony serve
```

L'application est accessible sur `http://localhost:8000` (ou le port défini).

---

## 🛠️ Commandes CLI

Le projet propose les commandes Symfony suivantes :

### `app:purge-reservations`

**Description** : Annule automatiquement les réservations en attente qui dépassent le délai de purge configuré.

**Usage** :
```bash
php bin/console app:purge-reservations
```

**Détails** :
- Supprime les réservations avec le statut `EN_ATTENTE` ayant dépassé le délai configurable
- Le délai est défini dans le fichier de configuration (paramètre `app.delai_purge_minutes`)
- À utiliser dans un cron job pour un nettoyage automatique régulier

---

### `app:rapport-ventes`

**Description** : Affiche un rapport de ventes pour un mois donné.

**Usage** :
```bash
php bin/console app:rapport-ventes YYYY-MM
```

**Exemple** :
```bash
php bin/console app:rapport-ventes 2026-05
```

**Détails** :
- Affiche le nombre de réservations confirmées pour le mois spécifié
- Calcule le chiffre d'affaires total du mois
- Format de date requis : `YYYY-MM` (par exemple `2026-05`)

---

### Commandes Doctrine courantes

```bash
# Voir l'état des migrations
php bin/console doctrine:migrations:status

# Créer une nouvelle migration après modification de l'entité
php bin/console make:migration

# Créer une nouvelle entité
php bin/console make:entity

# Voir les requêtes exécutées
php bin/console doctrine:query:sql "SELECT * FROM user"
```

---

## 📨 Worker Messenger

Le projet utilise Symfony Messenger pour les files d'attente de messages. Deux messages sont gérés :

- `ConfirmationReservationMessage` : Envoi d'une confirmation après réservation
- `RappelEvenementMessage` : Rappel d'un événement à venir

### Lancer le worker Messenger

```bash
php bin/console messenger:consume doctrine
```

**Options** :

```bash
# Limiter à 100 messages
php bin/console messenger:consume doctrine --limit=100

# Lancer le worker une seule fois puis arrêter
php bin/console messenger:consume doctrine --limit=1

# Définir un timeout (secondes)
php bin/console messenger:consume doctrine --time-limit=3600

# Mode verbose pour debug
php bin/console messenger:consume doctrine -v

# Traiter uniquement un type de message spécifique
php bin/console messenger:consume doctrine --routes=App\\Message\\ConfirmationReservationMessage
```

### Configuration du transport

Le transport Messenger est configuré dans `.env` :

```bash
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

Cela utilise la base de données PostgreSQL pour stocker les messages en file d'attente.

### Lancer un worker en continu en production

Pour un déploiement en production, utilisez un gestionnaire de processus (supervisor, systemd, etc.) :

**Exemple avec Supervisor** (`/etc/supervisor/conf.d/messenger.conf`) :

```ini
[program:ticketzone-messenger]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/TicketZone-Symfony/bin/console messenger:consume doctrine --time-limit=3600
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/messenger.log
user=www-data
environment=APP_ENV=prod
```

---

## ⚙️ Configuration .env.local

Créez un fichier `.env.local` à la racine du projet avec les variables suivantes :

### Exemple de `.env.local`

```bash
# Symfony Framework
APP_ENV=dev
APP_SECRET=599cd3c2f5193b54bf9e08882f2f9baf

# Base de données PostgreSQL
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"

# Alternative MySQL/MariaDB
# DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"

# Messenger Transport
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0

# Mailer (optionnel)
MAILER_DSN=null://null

# URI par défaut pour les URLs en CLI
DEFAULT_URI=http://localhost:8000

# Partage de répertoire
APP_SHARE_DIR=var/share
```

### Variables disponibles

| Variable | Description | Valeur par défaut |
|----------|-------------|------------------|
| `APP_ENV` | Environnement (dev/test/prod) | `dev` |
| `APP_SECRET` | Clé secrète Symfony | (vide) |
| `DATABASE_URL` | URL de connexion à la base de données | `postgresql://...` |
| `MESSENGER_TRANSPORT_DSN` | Transport pour la file d'attente Messenger | `doctrine://default?auto_setup=0` |
| `MAILER_DSN` | Configuration du service de mail | `null://null` |
| `DEFAULT_URI` | URI par défaut pour les commandes CLI | `http://localhost` |
| `APP_SHARE_DIR` | Répertoire de partage | `var/share` |
| `POSTGRES_DB` | Nom de la base de données PostgreSQL | `app` |
| `POSTGRES_USER` | Utilisateur PostgreSQL | `app` |
| `POSTGRES_PASSWORD` | Mot de passe PostgreSQL | `!ChangeMe!` |
| `POSTGRES_VERSION` | Version de PostgreSQL (Docker) | `16` |

### Paramètres d'application personnalisés

Vous pouvez ajouter dans `.env.local` des paramètres personnalisés :

```bash
# Délai de purge des réservations (en minutes)
APP_DELAI_PURGE_MINUTES=30
```

---

## 📚 Ressources utiles

- [Documentation Symfony 7.4](https://symfony.com/doc/7.4/index.html)
- [Documentation Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [Symfony Messenger](https://symfony.com/doc/7.4/messenger.html)
- [Docker et Docker Compose](https://docs.docker.com/)

---

## 📝 Notes supplémentaires

- Ne jamais commiter le fichier `.env.local` (secrets locaux)
- Pour les modifications d'entités, relancer les migrations : `php bin/console make:migration`
- Le worker Messenger doit tourner en continu en production
- Vérifier les logs : `tail -f var/log/dev.log`

---

**Dernière mise à jour** : Mai 2026
