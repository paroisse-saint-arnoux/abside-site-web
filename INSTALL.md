# Installation et mise en production

Site de L'Abside : **https://abside-culture.fr**, sur l'hébergement mutualisé **O2Switch** (cPanel, PHP 8.5, MySQL, SSH).
Le serveur suit directement la branche **`main`**. Le front compilé (`public/build`) est versionné, car il n'y a pas de npm sur le serveur.
La mise en ligne se fait à la main, en SSH, avec `./deploy.sh` (git pull, composer, artisan). GitHub n'a aucun accès au serveur.

```
poste de dev : npm run build, commit (public/build compris), push sur main
                 └─► GitHub Action « Tests » : tests + contrôle que public/build est à jour
serveur O2Switch : ./deploy.sh  →  git pull main, composer install, migrations, caches
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
git clone https://github.com/paroisse-saint-arnoux/abside-site-web.git abside-culture.fr
cd abside-culture.fr
composer install --no-dev --no-interaction --optimize-autoloader
cp .env.example .env
php artisan key:generate --force    # écrit APP_KEY dans .env (à faire une seule fois, avant le premier deploy.sh)
nano .env                       # compléter (voir ci-dessous)
./deploy.sh                     # composer, migrations, contenu de départ, lien storage, clés OAuth, caches
```

Le dépôt est public : le clonage en HTTPS ne demande aucune clé.

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

Si la commande `php` du terminal n'est pas en version 8.5, lancer le script avec le bon binaire, par exemple `PHP=/opt/alt/php85/usr/bin/php ./deploy.sh`. Il faudra alors utiliser le même chemin dans le cron.

### Démarrer avec le contenu préparé en local (facultatif)
Sans rien importer, le premier `./deploy.sh` crée de lui-même le contenu de départ (pages, saison 2026-2027, images), à partir des migrations.
Pour reprendre exactement le contenu de la base locale, procéder ainsi **avant** le premier `./deploy.sh` :

```bash
mysql -u UTILISATEUR -p NOM_DE_LA_BASE < abside-demarrage.sql     # base vide : structure, contenu, migrations déjà marquées
mkdir -p storage/app/public && tar -xzf abside-images.tar.gz -C storage/app/public
./deploy.sh                                                       # « Nothing to migrate » : le contenu importé est conservé
```

Le SQL ne contient ni sessions, ni cache, ni jetons, ni inscriptions. Il inclut l'administrateur local.
Les images de partage (`storage/app/public/partage`) sont recréées à la première visite.

### Première connexion à l'admin
Aller sur https://abside-culture.fr/admin et saisir l'adresse `ADMIN_EMAIL` : un lien de connexion arrive par e-mail. Il n'y a pas de mot de passe.
Les autres personnes sont ajoutées ensuite dans **Admin → Utilisateurs**.

## 3. Tâches planifiées (cron cPanel)

Dans cPanel, menu **Tâches Cron**, ajouter une ligne qui s'exécute **chaque minute** (`* * * * *`). Remplacer `abside-culture.fr` par le nom du dossier s'il est différent :

```
cd ~/abside-culture.fr && php artisan schedule:run >> /dev/null 2>&1
```

Ce cron déclenche les tâches définies dans `routes/console.php` :

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

1. **En local** : après une modification SCSS ou JS, lancer `npm run build` et commiter `public/build` avec le reste.
   La GitHub Action **Tests** (`.github/workflows/tests.yml`) échoue si `public/build` n'est pas à jour, ou si un test échoue.
2. Fusionner dans **`main`** et pousser, en attendant que l'Action soit au vert.
3. **Sur le serveur**, en SSH :

   ```bash
   cd ~/abside-culture.fr && ./deploy.sh
   ```

`deploy.sh` affiche une page de maintenance de quelques secondes, puis enchaîne :
- `git pull --ff-only origin main` ;
- `composer install --no-dev` ;
- `migrate --force` ;
- `storage:link` et clés OAuth (une seule fois) ;
- `optimize` et `filament:optimize`.

On peut le relancer sans risque, par exemple après une modification du `.env`. En cas de blocage, `php artisan up` lève la page de maintenance.
Le `git pull` refuse de fusionner si des fichiers suivis par git ont été modifiés sur le serveur : il ne faut rien modifier à la main sur le serveur.

Les fichiers ignorés par git ne sont jamais touchés par une mise à jour : `.env`, `vendor/`, et `storage/` (images envoyées depuis l'admin, images de partage, journaux).

## 5. Sauvegardes et suivi

- **Sauvegardes** : JetBackup d'O2Switch (fichiers et bases, restauration depuis cPanel). Les données à protéger sont la base MySQL et `storage/app/public` (images).
- **Journaux** : `storage/logs/laravel-AAAA-MM-JJ.log` (erreurs, échecs d'envoi d'e-mail ou d'appel à Brevo) 
- **E-mails non reçus** : vérifier les journaux d'envoi de Brevo (Transactionnel → Journaux), puis `storage/logs`.

## Développement local

Voir [CLAUDE.md](CLAUDE.md), section « Développement (Docker) ». En bref : `docker compose up -d`, puis le site sur http://localhost:8090, l'admin sur `/admin` et les e-mails dans Mailpit sur http://localhost:8026.
