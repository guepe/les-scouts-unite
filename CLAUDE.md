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

Il n’y a pas de test unitaire isolé : `tests/tests.php` est un seul script, exécuté dans le Playground après `playground/contenu-demo.php`, avec un helper `$ok( condition, message )`. Pour ajouter un test, ajoute un `$ok(…)` dans la rubrique concernée.

## Architecture

Thème (racine) :
- `functions.php` : styles de blocs, catégories de compositions, filtres de rendu (liens en sous-dossier, IAmA sous la règle des âges), avis si l’extension manque, puis `inc/section.php` (données des sections et composition commune des pages de section).

Extension (`extension/`) :
- `les-scouts-unite-extension.php` : charge `inc/` sur `after_setup_theme`, **sauf** si un thème d’avant la 3.0 définit encore `scoutsunite_agenda_sections()` (il attend alors la mise à jour du thème, avec un avis). Helpers `scoutsunite_extension_url()`, `scoutsunite_extension_version()`, `scoutsunite_extension_theme_url()` (un fichier du thème actif, '' s’il n’existe pas).
- `inc/` : un fichier par fonction, chacun avec un en-tête qui explique son rôle.
  - `agenda.php` : type de contenu `reunion` (archive `/agenda/`), champs `su_*`, répétition hebdomadaire, annulation, colonnes d’administration et les blocs dynamiques de l’agenda.
  - `droits.php` : rôles `scoutsunite_animateur` et `scoutsunite_staff`, limites appliquées par `map_meta_cap` (éditeur, REST et XML-RPC). Les sections d’un animateur sont dans la méta utilisateur `scoutsunite_sections`.
  - `unite.php` : page **Réglages → Unité** (coordonnées, staffs, tarifs, Bon à savoir…) et la source de liaisons de blocs `scoutsunite/unite` (argument `key`) qu’utilisent les compositions du thème. Un champ vide garde son exemple surligné.
  - `documents.php` : type de contenu `document` et son bloc.
  - `calendrier.php` : flux iCalendar (`?ical`) et liens sous les blocs de l’agenda.
  - `imprimer.php` : feuilles A4 « à mettre sur le frigo » (`?imprimer`, `?mois=`), stylées par `assets/css/imprimer.css` et les logos **du thème**.
  - `whatsapp.php` : liens des groupes et QR codes en SVG, dessinés par `inc/lib/qrcode.php` (bibliothèque tierce, ne pas modifier, exclue de PHPCS).
- `assets/js/blocs.js` (blocs de l’éditeur, aperçu par `ServerSideRender`) et `assets/js/reunion.js` (panneau « Quand et où ? ») : **pas de build**, ES5 avec `wp.element.createElement`. Pas de JSX, ni d’`import`, ni de syntaxe ES6+.

La frontière : l’extension ne doit appeler aucune fonction du thème, et le thème aucune fonction de l’extension sans `function_exists()`. Le thème ne doit jamais redéfinir une fonction de l’extension (erreur fatale). Le CSS des blocs reste dans le `style.css` du thème.

Le reste du thème :
- `templates/`, `parts/` : modèles HTML du FSE. `patterns/` : compositions PHP (catégories `scoutsunite` et `scoutsunite-pages`). `theme.json` en version 3, validé contre le schéma de WordPress 6.6.
- `style.css` : en-tête du thème (avec `Version:`) et tout le CSS. `assets/css/imprimer.css` sert aux feuilles à imprimer.
- `playground/` : contenu de démonstration, partagé par le site local, les tests et la démo en ligne (`demo.json`, qui installe le thème et l’extension depuis GitHub).

## Conventions

- Préfixes : `scoutsunite_` (fonctions PHP, options), `scoutsunite/` (blocs), `scoutsunite-` (scripts), `su_` (champs des réunions). Domaine de traduction : `les-scouts-unite`.
- Compatibilité : **PHP 7.4** et **WordPress 6.6** minimum. Pas d’opérateur `?->`, de `match`, de propriétés typées, d’`str_contains`, etc.
- Sécurité : tout ce qui sort passe par `esc_*` ; nonces et assainissement des données reçues ; SQL via `$wpdb->prepare`. `scoutsunite_image()` renvoie déjà une adresse échappée.
- Liste des sections : `baladins`, `louveteaux`, `eclaireurs`, `pionniers`, plus `unite` (toute l’unité) et `iama` (clan des anciens) pour l’agenda. Partir de `scoutsunite_agenda_sections()` / `scoutsunite_sections()` plutôt que de recopier la liste.
- Rien de propre à une unité n’est écrit dans le code : le nom vient du titre du site, les coordonnées de Réglages → Unité. Les exemples à remplacer sont surlignés (classe `a-completer`).
- Pour un changement de droits, augmente `SCOUTSUNITE_DROITS_VERSION` dans `extension/inc/droits.php` : les rôles sont réécrits au prochain chargement.
- Les pages déjà créées par les unités gardent le HTML des compositions d’alors. Si possible, fais passer un changement par le rendu (filtres `render_block*`, blocs dynamiques) plutôt que par une composition seule, et sinon, explique la mise à jour dans le CHANGELOG ou le README.
- Textes visibles : tutoiement, phrases simples, typographie française (apostrophe `’`, guillemets « », espaces avant `:`).

## Publier une version

1. Monter `Version:` dans `style.css` **et** dans `extension/les-scouts-unite-extension.php` (même numéro, vérifié par la CI et un test), et ajouter une rubrique `## X.Y.Z – AAAA-MM-JJ` dans `CHANGELOG.md` (la CI refuse la publication sans elle).
2. Commiter, puis `git tag vX.Y.Z && git push origin main vX.Y.Z`.
3. L’action `release` relance `verifier`, contrôle la version et le CHANGELOG, construit les deux zips avec `git archive` (thème : `.gitattributes` exclut doc, outils et `extension/` ; extension : `HEAD:extension`) et publie la Release.

Quand une version change ce que voient les animateurs ou le staff, mettre aussi à jour les modes d’emploi dans `docs/` (textes et fiches illustrées).
