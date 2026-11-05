# CLAUDE.md — Site de L'Abside

Site de **L'Abside**, centre culturel et touristique catholique des Alpes-Maritimes (association, SIREN 992 749 168, 23 avenue Sévigné, 06100 Nice) : agenda d'événements culturels dans des lieux de culte (théâtre, conférences, concerts, expositions, visites guidées, événements).
Production prévue : **https://abside-culture.fr** — hébergement mutualisé **O2Switch** (même fonctionnement que le site Saint Joseph, `~/perso/saint-joseph`, dont ce projet reprend la stack et les briques).

Ce fichier consigne les décisions prises avec Bertrand Gorge. **Le tenir à jour** à chaque nouvelle décision.

Maquettes validées (piste **Façade**) et planche « Structure » qui sert de cahier des charges : https://claude.ai/artifact/QxpvWrdwNZ7FkJBSikqp3P (fichiers `C-*.dc.html`).

## Stack

- **Laravel 13** + **Filament 5** (admin en français, `/admin`), Livewire 4, Passport (prévu pour le connecteur Claude et l'API).
- Front : Blade + **SCSS + Bootstrap 5.3** (import sélectif), Vite, JS vanilla en modules.
- Polices auto-hébergées : **Marcellus** (titres) et **Figtree** (texte), via `@fontsource`. Pas de Google Fonts.
- Images : `App\Support\Images` (redimensionnement 1920 px max, WebP). **Original gardé** sur le disque privé (`storage/app/private/image-sources/…`, même nom que la copie, + `.json` : largeur max, dernière retouche). « Recadrer / pivoter » (Cropper.js, `filament/image-cropper`) repart toujours de l'original et crée une copie sous un nouveau nom. Une image remplacée ou retirée (événement, mot clé, réglage) est supprimée avec son original à l'enregistrement, sauf si une autre fiche l'utilise encore (`Images::cleanup`, `isUsed`). Champ `App\Filament\Forms\ImageUpload` : glisser-déposer sur tout le champ et bouton « Remplacer » (`resources/js/admin/images.js`).
- Markdown : `App\Support\Markdown` (`league/commonmark` GFM, HTML brut autorisé, vidéos YouTube intégrées).

## Développement (Docker)

```bash
docker compose up -d                       # app :8090, MySQL :3310, Mailpit :8026, DbGate :3001
docker compose exec -u www-data -e HOME=/tmp app php artisan migrate
docker compose exec -u www-data -e HOME=/tmp app php artisan test
npm install && npm run dev                 # ou : docker compose --profile front up node (port 5174)
npm run build                              # build de production dans public/build
```

- Ports choisis pour ne pas entrer en conflit avec les autres projets locaux (okovision occupe 3000, 3306, 8080, 8081).
- Lancer PHP/Composer/artisan **dans le conteneur**.
- Emails en local : Mailpit http://localhost:8026. Admin local : http://localhost:8090/admin (lien magique dans Mailpit), ou `php artisan admin:lien` pour un lien direct (hors production).
- `Assets et photos/` : photos, affiches et logos bruts fournis par le client, **hors git** (lourds). Les images utiles au site sont copiées, redimensionnées, dans `public/images/`.

## Décisions

### Identité (piste Façade)
- Ocre `#E9BE4A`, crème `#FDF8EC`, bleu nuit du logo `#152E47`. Une couleur par type (texte blanc dessus) : théâtre `#1F4E8C`, conférence `#B4492C`, concert `#6A3D7C`, expo `#7A5A12`, visite guidée `#2F5D50`, événement `#152E47` (`App\Enums\EventType::color()` et `$type-colors` dans `_variables.scss`, à garder identiques).
- Motif de la voûte du logo : images et blocs aux coins supérieurs en plein cintre.
- Logo : `public/images/logo-abside.svg` (bleu) et `logo-abside-blanc.svg`. Le fichier source blanc (`L'abside_Blanc.svg`) n'a pas le même cadrage que la version bleue (dessin décalé d'environ 20 unités vers le haut) : son `viewBox` est recadré sur son propre dessin (`100.5 120 412 290`).

### Navigation
- En-tête : logo, « Tout l'agenda », bouton « Soutenir », puis les **six types** en grand (Théâtre, Conférences, Concerts, Expo, Visites guidées, Événements = tout le reste) → agenda filtré (`/agenda?type=…`).
- Pied de page : Qui sommes-nous, Contact et presse, Soutenir l'association, réseaux, coordonnées, mentions légales, confidentialité.
- Bande « Recevoir les informations de L'Abside » (newsletter) sur chaque page.

### Contact
- **Un seul contact pour tout le site** (décision du 05/11/2026) : abside.association@gmail.com, 06 14 47 30 42, y compris pour les événements signés « Service Culture du diocèse ». Toutes les inscriptions arrivent sur ce Gmail.
- Expéditeur des e-mails : une adresse du domaine (`contact@abside-culture.fr`), authentifiée chez Brevo (SPF/DKIM) ; réponse vers le Gmail. Envoyer « depuis » le Gmail via Brevo échouerait aux contrôles DMARC.

### Événements (étape 2, 05/11/2026)
- Modèle : `Event` (type, lieu, mots clés, chapô, description Markdown, photo, affiche, galerie, **caractéristiques** clé/valeur ≤ 6, partenaires, entrée, inscription, mise en avant, exergue, brouillon/publié + lien d'aperçu), `EventDate` (**une ligne par séance**, sous-titre facultatif), `Place` (nom, adresse, GPS), `Keyword` (page facultative).
- Dates : séances saisies une à une, ou **horaires récurrents** (période, jours, créneaux, fermetures) → séances générées à chaque enregistrement (`Event::regenerateRecurringDates`).
- Lieux : coordonnées GPS recherchées automatiquement à partir de l'adresse (`App\Support\Geocoder`, API Adresse de l'État, gratuite).
- Ligne de résultat de l'agenda : 125 px de haut (bloc date 100 px, visuel 220 × 125 recadré au centre).
- **Agenda** (`/agenda`, `App\Support\Agenda`) : filtres type, mois, mot clé, lieu, zone de carte (`?type=&mois=&mot-cle=&lieu=&zone=&passes=1`), filtrés en mémoire ; chaque filtre compte ses résultats avec les autres filtres actifs. Sans JS : liens et formulaire ; avec JS (`resources/js/modules/agenda.js`) : rechargement partiel (en-tête `X-Agenda: 1` → JSON). Filtres de zone, de lieu ou de mois en `noindex`.
- **Carte** : MapLibre GL + tuiles **OpenFreeMap** (style positron, gratuites, sans clé), couleurs retouchées à la charte. Déplacer ou zoomer ajoute « Recherche par lieu » ; le retirer recentre sur tous les lieux. Sans WebGL, la carte est masquée.
- **Accueil** : « À ne pas manquer » = `Event::featuredForHome()` (4 à venir : cochés « Mettre en avant » par date de fin la plus proche, complétés par les prochains) ; bandeau de nuit facultatif (`home.highlight`).
- **Exergue** de l'agenda : case distincte (`is_spotlight`), affichée si l'événement correspond aux filtres.
- Mot clé avec page → `/mot-cle/{slug}` (gabarit d'un événement + liste de ses événements) ; sans page → redirection vers l'agenda filtré (`Keyword::url()`).
- **Image de partage** générée (`App\Support\ShareImage`, GD, gabarit « Nocturne » : photo plein cadre, dégradé, logo blanc, type, titre, date), cache dans `storage/app/public/partage/`, adresse versionnée. Polices TTF (licence OFL) dans `resources/fonts/`.
- **JSON-LD** : une entrée par séance à venir ; TheaterEvent, EducationEvent (conférences), MusicEvent, ExhibitionEvent, sinon Event.
- Admin : « Dupliquer » un événement (tout sauf les dates, en brouillon) pour la saison suivante.
- Saison 2026-2027 reprise des affiches par la migration `seed_events` → `EventsSeeder` (visuels recadrés dans `database/seeders/assets/evenements/`).

### Inscriptions (décisions du 05/11/2026)
- **Réglable par événement** : inscription désactivée, facultative ou obligatoire, jauge facultative ; jauge atteinte → formulaire fermé, « Complet ». Pas de liste d'attente.
- **Rappel automatique la veille** (e-mail Brevo : date, heure, adresse, lien Google Maps), par tâche planifiée (cron cPanel).
- **RGPD** : inscriptions et contacts conservés **3 ans après la dernière participation**, puis supprimés automatiquement.
- Mise en œuvre (étape 3) :
  - `Contact` (une fiche par e-mail, en minuscules ; `created_at` = date d'ajout ; `newsletter_at`) et `Registration` (contact × séance, nombre de places ≤ 10, `starts_at` recopié de la séance, jeton pour le .ics et l'annulation). Une seule inscription par contact et par séance : se réinscrire met à jour le nombre de places.
  - `App\Support\Booking::book()` : création ou mise à jour du contact et de l'inscription, **jauge par séance** sous verrou (formulaire du site et ajout manuel dans l'admin, qui peut forcer la jauge).
  - Formulaire (`events/partials/registration`) : date (si plusieurs séances, places restantes affichées sous 10), prénom, nom, e-mail, téléphone facultatif, places, case newsletter non cochée (→ Brevo). Après envoi : confirmation, « Ajouter à mon agenda » (.ics) et Google Agenda. Toutes les dates complètes → « Complet » dans le bandeau.
  - E-mails (`app/Mail/Registration*`, SMTP Brevo) : confirmation au participant (avec .ics, lien d'annulation), alerte au contact de l'Abside (réponse directe au participant, places restantes), annulation, rappel la veille. Un e-mail en échec n'empêche pas l'inscription.
  - Annulation par le participant : `/inscription/{jeton}/annuler` (page avec bouton, pour que les robots de messagerie n'annulent pas en suivant le lien).
  - Tâches planifiées (`routes/console.php`) : `inscriptions:rappels` à 10 h (séances du lendemain), `contacts:purger` à 3 h 30. **En production : cron cPanel `* * * * * php artisan schedule:run`.**
  - Séances récurrentes : à l'enregistrement, les séances inchangées sont conservées (et donc leurs inscriptions).
  - Admin : onglet **Inscriptions** sur chaque événement (liste par séance, ajout par téléphone, export Excel), colonne « Inscrits » et action « Inscrits (Excel) » dans la liste des événements, menu **Contacts** (nombre d'événements, dernier événement, date d'ajout, newsletter ; export Excel).
  - Exports `.xlsx` : `App\Support\Spreadsheet` (OpenSpout) ; les textes ne sont jamais interprétés comme des formules.

### Pages éditables
- Admin > **Page d'accueil** (`ManageHome`, clé `home`) : introduction, **quatre blocs** (titre, sous-titre, couleur parmi les six des types, lien), qui sommes-nous. Blocs : formulaire à gauche, aperçu à droite ; on peut coller l'adresse complète d'une recherche (`ManageHome::normalizeUrl` garde le chemin et, pour l'agenda, les seuls filtres) ; bouton pour tester le lien.
- Admin > **Réglages du site** (`ManageSite`) : coordonnées (`site`), pages Qui sommes-nous / Soutenir / Presse (`pages`), pages légales (`legal`).
- Contenu de départ posé par la migration `seed_initial_content` → `InitialContentSeeder` (idempotent, ignoré en tests).

### Indexation
- `robots.txt` est une route : seule `abside-culture.fr` (`config('app.public_host')`) est indexable ; sur une autre adresse (recette `abside.saint-arnoux.fr`), robots.txt interdit tout et les pages sont en `noindex`.

### Formulaires
- Newsletter : **Brevo** API (`App\Services\Brevo`, `BREVO_API_KEY`, `BREVO_LIST_ID`), sans double opt-in.
- Contact : e-mail à `CONTACT_EMAIL`, reply-to = visiteur, non stocké.
- Anti-spam (`App\Support\SpamGuard`, partial `partials/spam-guard`) : champ piège + horodatage chiffré + Turnstile facultatif.

### Admin & accès
Repris tels quels de Saint Joseph : connexion par **lien magique** uniquement, connexion de 400 jours, rôles admin / editor, liste blanche `users`, barre Enregistrer / Annuler fixée seulement en cas de modifications non enregistrées.

## Feuille de route
1. **Fondations** — fait : squelette, charte, en-tête et pied de page, accueil, pages éditables, contact, newsletter.
2. **Événements** — fait : lieux, mots clés (et pages de mot clé), dates ponctuelles ou récurrentes, caractéristiques (6 max), galerie, affiche, partenaires ; agenda (filtres type, mois, mot clé, lieu, carte MapLibre) ; fiche événement ; mises en avant de l'accueil et exergue de l'agenda ; images de partage générées ; JSON-LD ; sitemap.
3. **Inscriptions et contacts** — fait : formulaire, jauge par séance, e-mails (confirmation, alerte, rappel la veille, annulation), « Ajouter à mon agenda » (.ics, Google), contacts, exports Excel, purge RGPD.
4. **Bibliothèque d'images, connecteur Claude (MCP), API, déploiement** (`deploy.sh` sur `main`), reprise des événements des affiches.

## Déploiement (O2Switch)
Procédure complète (comptes, `.env`, crons, mises à jour) : [INSTALL.md](INSTALL.md).
**Différent de Saint Joseph** (décision du 05/11/2026) : pas de branche `production`, le serveur suit **`main`**. `public/build` est **versionné** (pas de npm sur le serveur) : lancer `npm run build` et commiter `public/build` après toute modification SCSS/JS (l'Action « Tests » échoue sinon). Mise en ligne à la main en SSH : `./deploy.sh` (git pull, composer, migrations, caches). Toute évolution de schéma ou de contenu initial passe par une migration.

## Conventions de code
- Code et commentaires en français pour le métier ; classes et méthodes en anglais (Laravel).
- Attributs PHP Laravel 13 (`#[Fillable]`, `#[Hidden]`) sur les modèles.
- Styles : variables de charte dans `resources/scss/_variables.scss`, un partial par zone.
- Blade : ne pas utiliser `@js(...)` dans un attribut de composant Filament.
- Tests : `tests/Feature` (SQLite mémoire), à lancer avant chaque commit.
