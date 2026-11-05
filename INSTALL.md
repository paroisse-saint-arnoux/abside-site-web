# Installation et mise en production

Site de L'Abside : **https://abside-culture.fr**, sur l'hébergement mutualisé **O2Switch** (cPanel, PHP 8.5, MySQL, SSH).
Même fonctionnement que le site Saint Joseph : GitHub prépare une branche `production`, le serveur la récupère avec `./deploy.sh`.
GitHub n'a aucun accès au serveur, et aucun secret n'est stocké chez GitHub.

```
push sur main ──► GitHub Action : tests, compilation SCSS/JS ──► branche production
                                                                     │
serveur O2Switch : ./deploy.sh (cron toutes les 5 min) ◄─────────────┘
                   git, composer, migrations, caches
```

## 1. Préparer les comptes

### O2Switch (cPanel)
1. **Domaine** : `abside-culture.fr` rattaché à l'hébergement. Sa racine (« Document Root ») pointe vers le dossier **`public`** du site, par exemple `~/abside-culture.fr/public`.
2. **HTTPS** : certificat Let's Encrypt via « Let's Encrypt SSL » ou « AutoSSL ».
3. **Base MySQL** : dans « Bases de données MySQL », créer une base, un utilisateur et son mot de passe, puis donner **tous les privilèges** à l'utilisateur sur la base.
4. **Version de PHP** : 8.5 pour le domaine, dans « Sélecteur de version PHP » ou « MultiPHP ». Extensions nécessaires : `gd`, `intl`, `zip`, `exif`, `bcmath`, `pdo_mysql`, `fileinfo`.
5. **SSH** : dans « Autorisation SSH », ajouter son adresse IP. L'accès SSH est limité aux IP autorisées.

### Brevo
1. **Domaine** : authentifier `abside-culture.fr` (enregistrements SPF, DKIM et DMARC dans la zone DNS de cPanel).
   L'expéditeur des e-mails est `contact@abside-culture.fr`, et les réponses partent vers le Gmail de l'Abside.
2. **Clé SMTP** (Paramètres → SMTP et API → SMTP) : elle sert aux e-mails de confirmation, d'alerte et de rappel, ainsi qu'au lien de connexion à l'admin.
3. **Clé API** (même page, onglet API) et **identifiant de la liste** newsletter (Contacts → Listes) : ils servent aux inscriptions à la newsletter.

### Cloudflare Turnstile (facultatif)
Anti-spam supplémentaire sur le formulaire de contact : créer un widget pour `abside-culture.fr` et récupérer ses deux clés.
Sans ces clés, le site se protège avec un champ piège et un contrôle de délai.

## 2. Installer le site sur le serveur (une seule fois)

En SSH :

```bash
cd ~
git clone -b production https://github.com/paroisse-saint-arnoux/abside-site-web.git abside-culture.fr
cd abside-culture.fr
composer install --no-dev --no-interaction --optimize-autoloader
cp .env.example .env
php artisan key:generate        # écrit APP_KEY dans .env
nano .env                       # compléter (voir ci-dessous)
./deploy.sh --force             # composer, migrations, contenu de départ, lien storage, clés OAuth, caches
```

Le dépôt est public : le clonage en HTTPS ne demande aucune clé. La branche `production` doit exister, donc il faut avoir poussé au moins une fois sur `main` (voir l'étape 4).

### Variables à compléter dans `.env`

| Variable | Valeur |
|---|---|
| `APP_URL` | `https://abside-culture.fr` |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | base créée dans cPanel (noms préfixés par le compte O2Switch) |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | identifiant et clé **SMTP** Brevo |
| `BREVO_API_KEY`, `BREVO_LIST_ID` | clé **API** Brevo et numéro de la liste newsletter |
| `CONTACT_EMAIL` | `abside.association@gmail.com` : reçoit les messages de contact et les alertes d'inscription |
| `ADMIN_EMAIL` | adresse du premier administrateur, créé par les migrations |
| `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` | facultatif |

Laisser `APP_ENV=production` et `APP_DEBUG=false`. Il n'y a pas de file d'attente (`QUEUE_CONNECTION=sync`) : les e-mails partent immédiatement.

Si la commande `php` du terminal n'est pas en version 8.5, lancer le script avec le bon binaire, par exemple `PHP=/opt/alt/php85/usr/bin/php ./deploy.sh --force`. Il faudra alors utiliser le même chemin dans les crons.

### Première connexion à l'admin
Aller sur https://abside-culture.fr/admin et saisir l'adresse `ADMIN_EMAIL` : un lien de connexion arrive par e-mail. Il n'y a pas de mot de passe.
Les autres personnes sont ajoutées ensuite dans **Admin → Utilisateurs**.

## 3. Tâches planifiées (cron cPanel)

Dans cPanel, menu **Tâches Cron**, ajouter deux lignes. Remplacer `abside-culture.fr` par le nom du dossier s'il est différent.

| Fréquence | Commande | Rôle |
|---|---|---|
| Chaque minute (`* * * * *`) | `cd ~/abside-culture.fr && php artisan schedule:run >> /dev/null 2>&1` | **Indispensable** : tâches du site (voir ci-dessous) |
| Toutes les 5 minutes (`*/5 * * * *`) | `cd ~/abside-culture.fr && ./deploy.sh >> storage/logs/deploy.log 2>&1` | Mise en ligne automatique des nouvelles versions (le script ne fait rien s'il n'y a pas de nouveauté) |

Le premier cron déclenche les tâches définies dans `routes/console.php` :

| Heure | Commande | Rôle |
|---|---|---|
| 10 h 00 | `inscriptions:rappels` | Rappel par e-mail aux inscrits des séances du lendemain (une seule fois par inscription) |
| 3 h 30 | `contacts:purger` | RGPD : suppression des contacts, et de leurs inscriptions, 3 ans après leur dernière participation |

**Sans ce cron, aucun rappel n'est envoyé et les données ne sont jamais supprimées.**

Pour vérifier en SSH :

```bash
php artisan schedule:list           # prochaines exécutions
php artisan inscriptions:rappels    # envoie tout de suite les rappels de demain (sans doublon)
```

## 4. Mettre à jour le site

1. Développer sur une branche, fusionner dans **`main`** et pousser.
2. La GitHub Action **Publication** (`.github/workflows/deploy.yml`) lance les tests, compile le front et met à jour la branche `production`. Si les tests échouent, rien n'est publié.
3. Le serveur récupère la nouvelle version au prochain passage du cron. Sans attendre, on peut lancer `./deploy.sh` en SSH.

`deploy.sh` affiche une page de maintenance de quelques secondes, puis enchaîne : `git checkout` de `production`, `composer install --no-dev`, `migrate --force`, `storage:link`, clés OAuth (une seule fois), puis `optimize` et `filament:optimize`.
Ses options :
- `./deploy.sh --force` relance tout, même sans nouveau commit (après une modification du `.env`, par exemple) ;
- en cas de blocage, `php artisan up` lève la page de maintenance.

Les fichiers ignorés par git ne sont jamais touchés par une mise à jour : `.env`, `vendor/`, et `storage/` (images envoyées depuis l'admin, images de partage, journaux).

## 5. Sauvegardes et suivi

- **Sauvegardes** : JetBackup d'O2Switch (fichiers et bases, restauration depuis cPanel). Les données à protéger sont la base MySQL et `storage/app/public` (images).
- **Journaux** : `storage/logs/laravel-AAAA-MM-JJ.log` (erreurs, échecs d'envoi d'e-mail ou d'appel à Brevo) et `storage/logs/deploy.log`.
- **E-mails non reçus** : vérifier les journaux d'envoi de Brevo (Transactionnel → Journaux), puis `storage/logs`.

## Développement local

Voir [CLAUDE.md](CLAUDE.md), section « Développement (Docker) ». En bref : `docker compose up -d`, puis le site sur http://localhost:8090, l'admin sur `/admin` et les e-mails dans Mailpit sur http://localhost:8026.
