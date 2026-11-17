# CLAUDE.md

Thème de bloc WordPress (Full Site Editing) pour le site d’une unité **Les Scouts asbl**, et son extension. Depuis la 3.0, le **thème** (à la racine du dépôt) fait l’apparence et l’**extension** (dossier `extension/`) le cœur : agenda, documents, droits, Réglages → Unité. Les deux sont publiés ensemble, au même numéro de version. Tout est en français : le code (noms de fonctions et de variables), les commentaires, l’interface, les commits et la doc. Les personnes qui tiennent le site sont des animateurs bénévoles, pas des développeurs.

Contexte produit : [PRODUCT.md](PRODUCT.md). Système graphique : [DESIGN.md](DESIGN.md). Spécification de l’agenda et des droits : [docs/agenda-module.md](docs/agenda-module.md). Feuille de route et user stories : `US.md` (document de travail, ignoré par git).

## Commandes

```sh
# Site local (WordPress Playground, Node suffit) → http://127.0.0.1:9400, admin / password
npx @wp-playground/cli@latest server --port=9400 \
  --mount=.:/wordpress/wp-content/themes/les-scouts-unite \
  --mount=./extension:/wordpress/wp-content/plugins/les-scouts-unite-extension --blueprint=./blueprint.json

# Tests dans un WordPress neuf (résultats aussi dans tests/resultats.txt)
tests/lancer.sh            # PHP 8.3, dernière version de WordPress
tests/lancer.sh 7.4 6.6    # versions minimales annoncées : à faire passer aussi

# Vérifications de la CI, en local
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
for f in extension/assets/js/*.js; do node --check "$f"; done
phpcs                      # règles de .phpcs.xml.dist (WPCS 3), bloquant en CI
```

Il n’y a pas de test unitaire isolé : `tests/tests.php` est un seul script, exécuté dans le Playground après `playground/contenu-demo.php`, avec un helper `$ok( condition, message )`, plus `$reunion( titre, metas )` pour créer une réunion et `$dans( jours )` pour une date relative. Les utilisateurs `admin`, `anim` (animateur des Baladins) et `staff` y sont déjà créés. Pour ajouter un test, ajoute un `$ok(…)` dans la rubrique concernée (`/* Rôles ---- */`, `/* Répétition chaque semaine ---- */`…). Un échec lève une exception en fin de script : le Playground, donc `lancer.sh` et la CI, s’arrêtent en erreur.

## Architecture

Thème (racine) :
- `functions.php` : styles de blocs, catégories de compositions, filtres de rendu (liens en sous-dossier, IAmA sous la règle des âges), avis si l’extension manque, puis `inc/section.php` (données des sections et composition commune des pages de section).
- Fonctions du thème que d’autres fichiers du thème utilisent : `scoutsunite_sections()` (nom, âges, logo, accroche des quatre sections), `scoutsunite_section_pattern( $slug )`, `scoutsunite_image( $chemin )` (adresse d’une image du thème), `scoutsunite_version( $fichier )` (version + date du fichier, pour le cache des navigateurs).

Extension (`extension/`) :
- `les-scouts-unite-extension.php` : charge `inc/` sur `after_setup_theme`, **sauf** si un thème d’avant la 3.0 définit encore `scoutsunite_agenda_sections()` (il attend alors la mise à jour du thème, avec un avis). Helpers `scoutsunite_extension_numero()` (version de l’extension), `scoutsunite_extension_url()`, `scoutsunite_extension_version()` (version + date du fichier, pour le cache), `scoutsunite_extension_theme_url()` (un fichier du thème actif, '' s’il n’existe pas). À l’activation, l’option `scoutsunite_agenda_flush` fait recalculer les adresses pour que `/agenda/` existe.
- `inc/` : un fichier par fonction, chacun avec un en-tête qui explique son rôle.
  - `agenda.php` : `scoutsunite_agenda_sections()` (les sections de l’agenda, `unite` et `iama` compris), type de contenu `reunion` (archive `/agenda/`), champs `su_*`, répétition hebdomadaire, annulation, colonnes d’administration et les blocs dynamiques de l’agenda.
  - `droits.php` : rôles `scoutsunite_animateur` et `scoutsunite_staff`, limites appliquées par `map_meta_cap` (éditeur, REST et XML-RPC). Les sections d’un animateur sont dans la méta utilisateur `scoutsunite_sections`.
  - `unite.php` : page **Réglages → Unité** (coordonnées, logo, contacts des sections, tarifs, Bon à savoir…) et la source de liaisons de blocs `scoutsunite/unite` (argument `key`) qu’utilisent les compositions du thème. Un champ vide garde son exemple surligné.
  - `documents.php` : type de contenu `document` et son bloc.
  - `calendrier.php` : flux iCalendar (`?ical`) et liens sous les blocs de l’agenda.
  - `imprimer.php` : feuilles A4 « à mettre sur le frigo » (`?imprimer`, `?mois=`), stylées par `assets/css/imprimer.css` et les logos **du thème**.
  - `staff.php` : type de contenu `animateur` (menu « Staff ») : une fiche par membre du staff, privée (ni page ni API), photo dans un dossier protégé servie par `admin-post.php`, reprise des anciennes lignes de Réglages → Unité, et cartes d'animateur à imprimer (`admin-post.php?action=scoutsunite_cartes`). Le bloc staff, la page Contact et le frigo lisent `scoutsunite_staff_fiches()`.
  - `photos.php` (+ `photos-stockage.php`, `photos-interface.php`, `photos-selection.php`, `photos-presentation.php`) : photos de camp, détaillées ci-dessous.
  - `whatsapp.php` : liens des groupes et QR codes en SVG, dessinés par `inc/lib/qrcode.php` (bibliothèque tierce, ne pas modifier, exclue de PHPCS).
- **Photos de camp** (3.5, voir « Les photos de camp » du README) : espace privé `/photos/` rendu par `scoutsunite_photos_requete()` sur `template_redirect` (page HTML autonome, sans `wp_head`, CSP stricte), hors thème. Données dans cinq tables `scoutsunite_photos_*` (`albums`, `images`, `sessions`, `journal`, `demandes` ; schéma versionné par `SCOUTSUNITE_PHOTOS_SCHEMA` : l’augmenter pour un changement de table) ; fichiers dans un dossier **hors racine web** (`scoutsunite_photos_dossier()`, ou la constante `SCOUTSUNITE_PHOTOS_DOSSIER` de `wp-config.php`), jamais dans la médiathèque. Règles à garder :
  - Droits : capacités `scoutsunite_photos` (animateurs, sections de leur méta) et `scoutsunite_gerer_photos` (staff). Un parent n’a pas de compte : clé de 36 caractères dérivée par HMAC (seule son empreinte est en base, voir `scoutsunite_photos_generer_cle()`), session en cookie vérifiée à **chaque** lecture (`scoutsunite_photos_peut_lire()`).
  - Verrou : `scoutsunite_photos_verrou()` sérialise les mutations (envoi, état, suppression, export, clé). **Ne pas le prendre pour ce qu’un parent fait** (`ouvrir`, `demander`) : il bloquerait les familles pendant les envois. Les parents s’appuient sur l’insertion atomique et sur la version de la clé.
  - Tags : toujours enregistrés avec `scoutsunite_photos_tags_json()` (objet JSON) et fusionnés avec `+`, jamais `array_merge` (il renumérote les clés numériques comme « 2024 »).
  - `scoutsunite_photos_dossier()` est mémorisé par requête et exige `DOCUMENT_ROOT`, sauf WP-CLI et cron (`wp_doing_cron()`), sinon le nettoyage ne tournerait jamais.
  - Blocs dynamiques `scoutsunite/photos` (encart) et `scoutsunite/photos-presentation` (page `/photos-de-camp/`, créée une fois, jamais écrasée). JS sans build : `photos.js` (envoi, visionneuse plein écran, défilement continu des familles), `photos-selection.js` (cœurs, demandes). La vue des familles (`.photos__galerie--famille`, 60 photos par page) et celle des animateurs (cases à cocher, 24 par page) partagent `scoutsunite_photos_galerie()` ; le cœur est un SVG (`scoutsunite_photos_coeur()`) rempli par `aria-pressed`, jamais un caractère. La clé voyage dans le fragment `#cle=`, jamais dans une requête.
- `assets/js/blocs.js` (blocs de l’éditeur, aperçu par `ServerSideRender`) et `assets/js/reunion.js` (panneau « Quand et où ? ») : **pas de build**, ES5 avec `wp.element.createElement`. Pas de JSX, ni d’`import`, ni de syntaxe ES6+.
- Blocs dynamiques (rendus en PHP) : `scoutsunite/agenda`, `prochaine-reunion`, `prochaines-reunions`, `reunion`, `a-la-une`, `camps`, `iama`, `documents`, `staff`, `whatsapp`, `photos`, `photos-presentation`. Un nouveau bloc se déclare côté PHP (`register_block_type` avec `render_callback`) **et** dans `blocs.js`.

La frontière : l’extension ne doit appeler aucune fonction du thème, et le thème aucune fonction de l’extension sans `function_exists()`. Le thème ne doit jamais redéfinir une fonction de l’extension (erreur fatale). Le CSS des blocs reste dans le `style.css` du thème.

Le reste du thème :
- `templates/`, `parts/` : modèles HTML du FSE. Les modèles `page-<section>.html` n’affichent que le contenu : la page de section vient de sa composition (`patterns/page-section-*.php` → `scoutsunite_section_pattern()`). `patterns/` : compositions PHP (catégories `scoutsunite` et `scoutsunite-pages`). `theme.json` en version 3, validé contre le schéma de WordPress 6.6.
- `style.css` : en-tête du thème (avec `Version:`) et tout le CSS. `assets/css/imprimer.css` sert aux feuilles à imprimer.
- `playground/` : contenu de démonstration, partagé par le site local, les tests et la démo en ligne (`demo.json`, qui installe le thème et l’extension depuis GitHub).

## Conventions

- Préfixes : `scoutsunite_` (fonctions PHP, options), `scoutsunite/` (blocs), `scoutsunite-` (scripts), `su_` (champs des réunions). Domaine de traduction : `les-scouts-unite`.
- Compatibilité : **PHP 7.4** et **WordPress 6.6** minimum. Pas d’opérateur `?->`, de `match`, de propriétés typées, d’`str_contains`, etc.
- Sécurité : tout ce qui sort passe par `esc_*` ; nonces et assainissement des données reçues ; SQL via `$wpdb->prepare`. `scoutsunite_image()` renvoie déjà une adresse échappée.
- Liste des sections : `baladins`, `louveteaux`, `eclaireurs`, `pionniers`, plus `unite` (toute l’unité) et `iama` (clan des anciens) pour l’agenda. Partir de `scoutsunite_agenda_sections()` (extension) ou `scoutsunite_sections()` (thème, les quatre sections seulement) plutôt que de recopier la liste ; l’extension n’a pas le droit d’appeler la seconde.
- Rien de propre à une unité n’est écrit dans le code : le nom vient du titre du site, les coordonnées de Réglages → Unité. Les exemples à remplacer sont surlignés (classe `a-completer`).
- Pour un changement de droits, augmente `SCOUTSUNITE_DROITS_VERSION` dans `extension/inc/droits.php` : les rôles sont réécrits au prochain chargement.
- Les pages déjà créées par les unités gardent le HTML des compositions d’alors. Si possible, fais passer un changement par le rendu (filtres `render_block*`, blocs dynamiques) plutôt que par une composition seule, et sinon, explique la mise à jour dans le CHANGELOG ou le README.
- Commits : `type: thème X.Y.Z – ce qui change pour l’unité` (`feat`, `fix`, `refactor`, `docs`, `ci`), en français ; le numéro seulement pour un commit de version.
- Textes visibles : tutoiement, phrases simples, typographie française (apostrophe `’`, guillemets « », espaces avant `:`).

## Publier une version

1. Monter `Version:` dans `style.css` **et** dans `extension/les-scouts-unite-extension.php` (même numéro, vérifié par la CI et un test), et ajouter une rubrique `## X.Y.Z – AAAA-MM-JJ` dans `CHANGELOG.md` (la CI refuse la publication sans elle).
2. Commiter, puis `git tag vX.Y.Z && git push origin main vX.Y.Z`.
3. L’action `release` relance `verifier`, contrôle la version et le CHANGELOG, construit les deux zips avec `git archive` (thème : `.gitattributes` exclut doc, outils et `extension/` ; extension : `HEAD:extension`) et publie la Release.

Quand une version change ce que voient les animateurs ou le staff, mettre aussi à jour les modes d’emploi dans `docs/` (textes et fiches illustrées).
