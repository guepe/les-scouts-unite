# Module « Agenda des réunions » pour un thème de bloc WordPress

Spécification d'un module existant (thème `les-scouts-unite`), à reproduire dans un autre site WordPress. Il est intégré au thème, sans extension. Adapte les préfixes, la liste des sections et les textes au site cible ; garde les comportements.

## Objectif

Les animateurs encodent chaque réunion une seule fois dans l'administration. L'accueil, les pages de section et une page `/agenda/` se mettent ensuite à jour seuls. Les réunions passées disparaissent du site et la suivante prend leur place. Aucune date n'est écrite à la main dans les pages.

Contraintes :
- WordPress 6.5 ou plus, thème de bloc (FSE), PHP 7.4 ou plus ;
- sans build : le JS de l'éditeur est en ES5 avec `wp.element.createElement` ;
- interface en français, à destination de bénévoles non techniques.

## Fichiers

| Fichier | Rôle |
|---|---|
| `inc/agenda.php` (≈ 560 lignes, chargé par `functions.php` via `require`) | type de contenu, champs, répétition, colonnes d'administration, requêtes, 6 blocs dynamiques |
| `assets/js/reunion.js` | panneau « Quand et où ? » dans l'éditeur d'une réunion |
| `assets/js/blocs.js` | déclaration des 6 blocs dans l'éditeur, avec un aperçu rendu par le serveur |
| `templates/archive-reunion.html` | page `/agenda/` |
| `templates/single-reunion.html` | page d'une réunion |
| `style.css` | styles des lignes, des filtres et de l'affiche (voir « Classes CSS ») |

Préfixe utilisé : `scoutsunite_` pour les fonctions PHP, `scoutsunite/` pour les blocs, `scoutsunite-` pour les scripts et `su_` pour les champs. Remplace-le partout par celui du site cible.

## Liste des sections : une seule source

`scoutsunite_agenda_sections()` renvoie `identifiant => nom affiché`, dans l'ordre des âges :

```php
'baladins' => 'Baladins', 'louveteaux' => 'Louveteaux', 'eclaireurs' => 'Éclaireurs',
'pionniers' => 'Pionniers', 'unite' => 'Toute l’unité'
```

- `unite` sert aux activités communes. Il est exclu du bloc « Prochaines réunions ».
- La liste est envoyée au JS de l'éditeur par `wp_add_inline_script( …, 'window.scoutsuniteSections = ' . wp_json_encode( … ), 'before' )`.
- Défaut actuel : `blocs.js` recopie cette liste en dur dans le sélecteur du bloc « Prochaine réunion d'une section ». Dans la nouvelle version, lis plutôt `window.…Sections`.

## Modèle de données

### Type de contenu `reunion`

- `public => true`, `show_in_rest => true` (indispensable pour l'éditeur de blocs) ;
- `has_archive => 'agenda'` et `rewrite => [ 'slug' => 'agenda', 'with_front' => false ]` ;
- `menu_name => 'Agenda'`, `menu_icon => 'dashicons-calendar-alt'`, `menu_position => 5` ;
- `supports => [ 'title', 'editor', 'revisions', 'custom-fields' ]` (`custom-fields` est nécessaire pour éditer les champs via REST) ;
- `template` : un seul paragraphe, avec l'indication « Notes pour les parents : à emporter, rendez-vous, remarques… (facultatif) ».

Le **contenu** de la réunion sert de notes pour les parents. Le **titre** est facultatif :
- le texte d'invite du titre est « Réunion, hike, week-end… » (filtre `enter_title_here`) ;
- un titre vide devient « Réunion » à l'enregistrement (filtre `wp_insert_post_data`, sauf pour le statut `auto-draft`) ;
- à l'affichage, le titre « Réunion » est masqué (`strcasecmp`). Seul un titre différent, comme « Hike », s'affiche.

### Champs

Tous sont enregistrés avec `register_post_meta( 'reunion', … )` :
- type `string`, `single => true`, `show_in_rest => true` ;
- `sanitize_callback => 'sanitize_text_field'` ;
- `auth_callback` : `current_user_can( 'edit_posts' )`.

| Clé | Format | Défaut | Rôle |
|---|---|---|---|
| `su_section` | identifiant de section | `''` | obligatoire |
| `su_date` | `Y-m-d` | `''` | obligatoire, sert au tri et au filtrage |
| `su_debut` | `H:i` | `''` | heure de début |
| `su_fin` | `H:i` | `''` | heure de fin |
| `su_lieu` | texte libre | `'au local'` | lieu |
| `su_repeter` | `Y-m-d` | `''` | « Répéter chaque semaine jusqu'au » |
| `su_type` | `''`, `'weekend'`, `'camp'` ou `'speciale'` | `''` | type d'activité ; `''` = réunion ordinaire |
| `su_jusqua` | `Y-m-d` | `''` | dernier jour d'un week-end ou d'un camp |
| `su_avant` | `'1'` ou `''` | `''` | « Mettre en avant sur l'accueil » : alimente le bloc « À la une » |
| `_su_repete_jusqua` | `Y-m-d` | — | champ privé : jusqu'où les copies ont déjà été créées |

La date est volontairement stockée en texte `Y-m-d` et non en timestamp : le tri alphabétique et la comparaison `DATE` suffisent.

## Éditeur d'une réunion (`reunion.js`)

Le panneau est un plugin `wp.plugins.registerPlugin` qui affiche un `PluginDocumentSettingPanel` (`wp.editor` ou, à défaut, `wp.editPost`) intitulé « Quand et où ? ». Il n'est chargé que si `get_current_screen()->post_type === 'reunion'`, sur le hook `enqueue_block_editor_assets`.

Champs du panneau :
- **Section** : `SelectControl`, avec une première option vide « Choisir… » ;
- **Type** : `SelectControl` Réunion / Week-end / Camp / Réunion spéciale ; seuls week-end et camp gardent `su_jusqua`, les autres types le vident ;
- **Date** (libellé « Du » pour un week-end ou un camp) : `TextControl type="date"` ;
- **Jusqu'au** : `TextControl type="date"`, affiché seulement pour un week-end ou un camp ;
- la liste des sections ne propose à un animateur que les siennes (voir « Droits »).
- **Début** et **Fin** : `TextControl type="time"`, côte à côte dans un `Flex` ;
- **Lieu** : `TextControl`, qui affiche « au local » si le champ est `undefined` ;
- **Mettre en avant sur l'accueil** : `CheckboxControl` qui enregistre `'1'` ou `''`, avec l'aide « Pour les grandes activités (week-end d'unité, souper, fête…) : elle s'affiche en grand en haut de l'accueil jusqu'à sa date. » ;
- **Répéter chaque semaine jusqu'au** : `TextControl type="date"`, avec l'aide « Facultatif. À la publication, une copie est créée chaque semaine jusqu'à cette date. Supprime ensuite les semaines de congé. »

Chaque modification appelle `editPost( { meta: { clé: valeur } } )`.

Garde-fous :
- **Ouverture automatique** : au montage, le panneau s'ouvre de lui-même (`toggleEditorPanelOpened` s'il est fermé). Si la section ou la date manque, la barre latérale du document s'ouvre aussi (`openGeneralSidebar( 'edit-post/document' )`).
- **Publication bloquée** : tant que la section ou la date manque, `lockPostSaving( 'scoutsunite-reunion' )` bloque la publication et un `Notice` d'avertissement non masquable affiche « Choisis la section et la date pour pouvoir publier. ».
- **Titre par défaut** : dès que les deux sont remplis, `unlockPostSaving` débloque la publication. Si le titre est vide, il devient « Réunion », car l'éditeur refuse de publier un contenu sans titre.

## Répétition hebdomadaire

Fonction `scoutsunite_agenda_repeter( $post )`, branchée sur `rest_after_insert_reunion` :

1. Elle s'arrête si `su_repeter` n'est pas une date `Y-m-d`, si `su_date` est vide, ou si `su_repeter <= su_date`.
2. Elle s'arrête si la réunion n'est pas publiée, ou si `_su_repete_jusqua === su_repeter` : les copies jusqu'à cette date existent déjà, ce qui rend l'opération idempotente.
3. Sinon, elle enregistre `_su_repete_jusqua = su_repeter`, puis crée une copie publiée par semaine (+7 jours), au maximum 60. Chaque copie reprend le titre, le contenu, l'auteur, la section, les heures, le lieu et la mise en avant, avec la nouvelle date.
4. Les copies sont des réunions indépendantes et ne portent pas `su_repeter`. On supprime les semaines de congé à la main.

Limites à connaître :
- La répétition ne se déclenche qu'à un enregistrement via REST, donc depuis l'éditeur de blocs, pas depuis la modification rapide.
- Repousser la date de fin plus tard crée de nouveau des copies à partir de la date d'origine, donc des doublons pour les semaines déjà faites. À corriger si possible : ne créer que les semaines situées après l'ancienne valeur de `_su_repete_jusqua`.

## Liste dans l'administration

- Colonnes : le titre, puis **Quand** (« samedi 24 octobre 14 h – 17 h »), **Section** (nom affiché, suivi de « · à la une » si la réunion est mise en avant) et **Lieu**.
- La colonne « Quand » est triable.
- Tri par défaut : sur `pre_get_posts`, pour la requête principale de l'admin `reunion` sans `orderby` (ou avec `orderby=su_quand`), tri par `meta_key=su_date`, `orderby=meta_value`, ordre ascendant par défaut.

## Lecture des réunions

`scoutsunite_agenda_a_venir( $section = '', $nombre = 100, $avant = false )` :
- `get_posts` sur `reunion` publiées, avec `posts_per_page => 300` ;
- `meta_query` : `su_date >= aujourd'hui` OU `su_jusqua >= aujourd'hui` (type `DATE`, dans le fuseau du site : un week-end en cours reste visible), `su_section` égal ou `IN` si on passe un tableau, `su_type IN $types` si on filtre par type, plus `su_section = $section` si on filtre, et `su_avant = '1'` si `$avant` ;
- tri en PHP par `su_date . su_debut` (`usort` + `strcmp`), donc une réunion sans heure passe en premier dans sa journée ;
- renvoie les `$nombre` premières.

Une réunion reste donc visible toute la journée où elle a lieu.

Fonctions de mise en forme :
- `agenda_jour( 'Y-m-d', $format = 'l j F' )` renvoie « samedi 24 octobre ». Elle construit la date à 12:00 dans `wp_timezone()` puis appelle `wp_date`, ce qui évite les décalages de jour.
- `agenda_quand( $id, $format )` renvoie la date, ou la période d'un week-end ou d'un camp : « du vendredi 13 au dimanche 15 novembre » (format long), « 13 – 15 novembre » (format `j F`). `agenda_type( $id )` renvoie « Week-end », « Camp » ou `''`, et `agenda_etiquette( $id )` le `span.ligne__type` placé devant la date dans les lignes.
- `agenda_heure( '14:30' )` renvoie « 14 h 30 », et `'14:00'` renvoie « 14 h ».
- `agenda_horaire( $id )` renvoie « 14 h – 17 h », « dès 14 h » s'il n'y a pas de fin, ou `''`.
- `agenda_section_url( $slug )` renvoie le permalien de la page `sections/{slug}` si elle existe, sinon `''`.
- `agenda_titre( $post )` renvoie `''` si le titre est « Réunion ».

## Blocs dynamiques

Les six blocs de l’agenda sont déclarés en PHP avec `register_block_type` :
- `api_version => 3`, catégorie `widgets` ;
- `editor_script => 'scoutsunite-blocs'` ;
- un `render_callback`, avec `save: () => null` côté JS.

Dans l'éditeur, l'aperçu passe par `wp.serverSideRender`. Chaque sortie est enveloppée dans `get_block_wrapper_attributes( [ 'class' => … ] )`.

Toutes les lignes partagent un même gabarit, `scoutsunite_agenda_ligne( $section, $nom, $quoi, $horaire, $notes )` :

```html
<div class="wp-block-group is-style-ligne-{section}"><p>{nom}</p><p>{quoi}</p><p>{horaire}</p>[<div class="ligne__notes">{notes}</div>]</div>
```

Les blocs :

1. **`scoutsunite/prochaines-reunions`** : « Prochaines réunions », pour l'accueil. Classe `agenda-prochaines`.
   - Une ligne par section, sauf `unite`.
   - Le nom de section renvoie vers sa page si elle existe.
   - Le contenu est « samedi 24 octobre · Hike · au local » avec l'horaire.
   - Sans réunion à venir, la ligne affiche `<span class="ligne__vide">pas de réunion prévue</span>`.
   - Le bloc se termine par un lien « Tout l'agenda » (`p.lien-fleche`) vers `get_post_type_archive_link( 'reunion' )`.

2. **`scoutsunite/prochaine-reunion`** : « Prochaine réunion d'une section », pour les pages de section. Classe `agenda-prochaine`.
   - Attribut `section` (`string`, défaut `baladins`), choisi dans un `SelectControl` de l'`InspectorControls`.
   - Affiche un `h2` « Prochaine réunion », la date en grand (`p.agenda-prochaine__date`, première lettre en majuscule), puis une ligne « Titre · horaire · lieu » et les notes complètes (`the_content`, dans `div.agenda-prochaine__notes`).
   - Ajoute ensuite « **Ensuite :** 31 octobre, 7 novembre (Hike), … » avec les 3 réunions suivantes et un lien « Voir l'agenda » vers `/agenda/?section={slug}`.
   - Sans réunion : « Pas encore fixée » et « Le staff publiera bientôt la prochaine date dans l'agenda. ».

3. **`scoutsunite/agenda`** : « Agenda complet », pour `/agenda/`. Classe `agenda`.
   - Navigation `nav.agenda__filtres` avec `aria-label="Filtrer par section"` : « Toutes » puis un lien par section, chacun avec la classe `agenda__filtre-{slug}`.
   - Le filtre est le paramètre `?section=` : `sanitize_key`, validé contre la liste des sections, en lecture seule. Le lien actif porte `aria-current="page"`.
   - Les réunions sont groupées par mois : `h2.agenda__mois` (« Octobre 2026 ») suivi de `div.agenda__liste`, au maximum 200 réunions.
   - Chaque ligne affiche « **samedi 24 octobre** · titre · lieu », l'horaire, et les notes réduites à 40 mots en texte brut.
   - Liste vide : `p.agenda__vide` « Aucune réunion à venir pour l'instant. Le staff publie les dates ici dès qu'elles sont fixées. ».

4. **`scoutsunite/a-la-une`** : « À la une », pour l'affiche blanche du panneau d'accueil. Classe `a-la-une`.
   - Prend les 4 prochaines réunions mises en avant (`a_venir( '', 4, true )`).
   - La première en grand : étiquette manuscrite avec le nom de section (`p.affiche__etiquette`), titre en `h2.affiche__titre` lié à la page de la réunion (sans titre : « Activité d'unité » pour `unite`, sinon « Réunion {section} »), puis `p.a-la-une__quand` avec la date (première lettre en majuscule) et « horaire · lieu », puis les notes réduites à 24 mots.
   - Les suivantes dans `p.a-la-une__suite` : « **Ensuite :** 13 novembre · Week-end d'unité », chacune liée à sa page. Pas de lien « Détails » : le titre mène déjà à la réunion.
   - Sans réunion mise en avant à venir : la dernière actualité (date en étiquette, titre lié, extrait de 22 mots, « Lire la suite »). Sans aucun article : « À la une » et une phrase d'attente.

5. **`scoutsunite/camps`** : « Temps forts de la section », sous le titre « Les temps forts » des pages de section. Classe `camps`.
   - Attribut `section`. Liste jusqu'à 6 week-ends, camps et réunions spéciales à venir de la section et de `unite` (`a_venir( [ $section, 'unite' ], 6, false, [ 'weekend', 'camp', 'speciale' ] )`).
   - Une fiche `article.camp` par activité : `p.camp__type` (« Camp », « Week-end · toute l'unité »), `h4.camp__titre` lié à la réunion, `p.camp__quand` (période, horaire, lieu), notes complètes dans `div.camp__notes`.
   - Sans activité : `p.camps__vide` qui explique comment en ajouter.

6. **`scoutsunite/reunion`** : « Détails de la réunion », pour la page d'une réunion. Classe `agenda-reunion`.
   - `uses_context => [ 'postId' ]`.
   - Affiche une seule ligne : la section, « date · lieu » et l'horaire.

## Modèles

- `archive-reunion.html` : en-tête, bandeau titre « Agenda » avec la mention « toutes les réunions à venir », un groupe contraint à 62rem contenant `<!-- wp:scoutsunite/agenda /-->`, puis le pied de page.
- `single-reunion.html` : en-tête, `post-title` en `h1`, `<!-- wp:scoutsunite/reunion /-->`, `post-content` (les notes), un lien « Tout l'agenda », puis le pied de page.

## Classes CSS à styler

- **Lignes** `[class*="is-style-ligne-"]` : grille de 3 colonnes (nom, quoi, horaire aligné à droite), qui passe sur 2 lignes sous 40rem.
  - La couleur de section vient de `--section`, définie par `.is-style-ligne-{slug}` ; un `::before` sur le premier enfant affiche une puce de cette couleur.
  - `.ligne__notes` s'étend sur toute la largeur sous la ligne, et `.ligne__vide` est en sourdine.
  - Ces mêmes classes `is-style-ligne-*` sont aussi déclarées comme styles du bloc Groupe (`register_block_style`), pour que l'éditeur puisse les réutiliser à la main.
- **Affiche** : `.agenda-prochaine__date`, `.agenda-prochaine__notes`, `.agenda-prochaine__suite`, et pour « À la une » `.a-la-une__quand` (gras), `.a-la-une__suite` (petit, filet au-dessus).
- **Agenda** : `.agenda__filtres a` (pastilles, couleur via `--section` sur `.agenda__filtre-{slug}`, `[aria-current="page"]` pour l'état actif), `.agenda__mois`, `.agenda__liste`, `.agenda__vide`.

## Droits (`inc/droits.php`)

- Le type `reunion` a ses propres droits : `capability_type => [ 'reunion', 'reunions' ]` et `map_meta_cap => true`. Administrateur et éditeur reçoivent `edit_reunions`, `publish_reunions`, etc. Les champs ne s'enregistrent que si `user_can( $user_id, 'edit_post', $post_id )` (`auth_callback`).
- Rôle **Animateur** (`scoutsunite_animateur`) : `read`, `upload_files`, `edit_pages`, `edit_others_pages`, `edit_published_pages`, `publish_pages` (sans lui, l'éditeur propose « Soumettre à la relecture » et la page quitte le site), tous les droits `*_reunions` et `*_documents`, et le droit repère `scoutsunite_animateur`. Ses sections sont dans la méta utilisateur `scoutsunite_sections` (tableau), cochées par un administrateur (`promote_users`) dans le profil.
- Rôle **Staff d'unité** (`scoutsunite_staff`) : les droits de l'éditeur, les droits `*_reunions` et `manage_unite`, qui ouvre Réglages → Unité (avec `option_page_capability_scoutsunite_unite`).
- Filtre `map_meta_cap` : pour un animateur (droit repère sans `manage_unite`), `edit_post`, `delete_post` et `publish_post` sur une réunion ne passent que si sa `su_section` est l'une des siennes (ou vide, pour une réunion qu'il crée) ; sur une page, seulement si son identifiant (`post_name`) est l'une de ses sections, et jamais la suppression.
- Le `sanitize_callback` de `su_section` ramène une section interdite à la première de l'animateur : il ne peut ni créer ni déplacer une réunion hors de ses sections.
- Dans l'administration, ses listes de réunions et de pages sont filtrées sur ses sections ; sur une page, l'éditeur est verrouillé en `templateLock: 'contentOnly'` (filtre `block_editor_settings_all`).
- Un animateur ne crée pas de page : `rest_pre_insert_page` refuse une nouvelle page, `load-post-new.php` refuse l'écran « Ajouter une page » et le sous-menu est retiré. Filet de sécurité sur `wp_insert_post_data` : une page publiée qu'il modifie garde le statut `publish` et son identifiant.
- Les rôles sont (ré)écrits quand `SCOUTSUNITE_DROITS_VERSION` change (option `scoutsunite_droits_version`, version 3 depuis la 2.10.1).

## Documents (`inc/documents.php`)

- Type de contenu `document` : `public => false`, `show_ui`, `show_in_rest`, `capability_type => [ 'document', 'documents' ]`, `supports => [ 'title', 'page-attributes' ]` (l'« Ordre »). Écran classique, sans éditeur de blocs : une boîte « Le document » (section, fichier choisi avec `wp.media`, explication), enregistrée sur `save_post_document` avec un nonce.
- Champs `su_section` (nettoyé par `scoutsunite_droits_section_permise`), `su_fichier` (ID d'une pièce jointe), `su_explication`. Un document sans fichier n'est affiché nulle part ; un avis le signale dans l'administration.
- Droits : `scoutsunite_droits_documents()` (les droits `*_documents`), donnés aux mêmes rôles que les réunions (`SCOUTSUNITE_DROITS_VERSION` passe à 2). `map_meta_cap` et le filtre des listes traitent `document` comme `reunion` : un animateur ne voit et ne modifie que les documents de ses sections, jamais ceux de toute l'unité.
- Bloc `scoutsunite/documents` (attribut `section`) : la section et `unite`, ou tous les documents rangés par section (`h2.documents__section`). Une ligne par document : type de fichier, titre lié au fichier, étiquette « toute l'unité », explication, type et taille.
- Reprise : `scoutsunite_reprise_documents()` ajoute, une seule fois, « Les documents » et le bloc après `scoutsunite/camps` dans les pages de section déjà créées.

## Questions fréquentes (`patterns/page-faq.php`)

- Composition « Page Questions fréquentes » : un groupe `.faq`, un `h2.faq__theme` par thème et des blocs `core/details` (question dans `summary`, réponse dedans), en exemples surlignés « à compléter ». La flèche est dessinée en CSS (deux traits à 45 degrés, qui pivotent à l'ouverture).

## Réunion annulée

- Champs `su_annulee` (`'1'` ou `''`) et `su_annulee_pourquoi` (texte d'une ligne), dans le panneau « Quand et où ? ». Décocher vide aussi l'explication. Les copies d'une répétition hebdomadaire ne reprennent pas l'annulation.
- `scoutsunite_agenda_annulee( $id )`, `scoutsunite_agenda_pourquoi( $id )`. `scoutsunite_agenda_etiquette()` ajoute `span.ligne__type--annulee` ; `scoutsunite_agenda_ligne()` reçoit `$annulee` et ajoute `ligne--annulee` (date, lieu et horaire barrés).
- « Prochaine réunion d'une section » et « Prochaines réunions » : `scoutsunite_agenda_prochaine_maintenue()` saute les réunions annulées et les rappelle au-dessus (`scoutsunite_agenda_rappel_annulees()`). Dans « Ensuite », une réunion annulée est barrée.
- Agenda complet et page de la réunion : seule l'explication remplace les notes. À la une et temps forts : étiquette et explication.
- Feuilles à imprimer : ligne grisée et barrée, étiquette « Annulée » ; convocation avec un bandeau « Cette activité est annulée ». Calendrier : `STATUS:CANCELLED`, `SUMMARY` préfixé « ANNULÉ – », explication en tête de la description. Liste de l'administration : « ANNULÉE – » dans la colonne Quand.

## Bon à savoir (`inc/unite.php`)

- Champ `{section}_infos` (textarea, une info par ligne) dans le groupe « Bon à savoir » de Réglages → Unité, pour chaque section d'âge.
- `scoutsunite_bon_a_savoir( $section, $exemple = true )` renvoie une liste `ul.bon-a-savoir__liste`. « Mot : texte » met le mot en gras. Un champ vide montre l'exemple surligné sur le site (`$exemple`), et rien sur les feuilles à imprimer.
- Affiché dans le bloc « Prochaine réunion d'une section » (`div.bon-a-savoir`, avant les liens, état vide compris) et sur le programme et la convocation imprimés (`section.feuille__bon`, sur deux colonnes). Au-delà de 8 réunions, la liste du programme imprimé passe elle aussi sur deux colonnes (`feuille__reunions--colonnes`).

## Calendrier iCalendar (`inc/calendrier.php`)

- Sur `template_redirect`, `?ical` sert un flux `text/calendar` à la place de la page : `/agenda/?section={slug}&ical` (une section d'âge reçoit aussi `unite`), `/agenda/?ical` (tout l'agenda), `/agenda/{réunion}/?ical` (un seul événement, en pièce jointe `reunion-{date}.ics`).
- Le flux contient les réunions publiées à venir et celles terminées depuis 30 jours. `UID` stable (`reunion-{ID}@{hôte}`), `LAST-MODIFIED` depuis `post_modified_gmt`, `REFRESH-INTERVAL` et `X-PUBLISHED-TTL` à 12 h.
- Une réunion avec heure : `DTSTART`/`DTEND` en UTC depuis `wp_timezone()`. Sans heure, ou week-end/camp sur plusieurs jours : dates entières (`VALUE=DATE`, fin exclusive), l'horaire passe dans la description (« Départ … à 18 h, retour … à 16 h. »).
- `SUMMARY` : « Section · titre » ; `LOCATION` : le lieu, suivi de l'adresse du local s'il contient « local » ; `DESCRIPTION` : les notes en texte brut et le lien de la réunion. Texte échappé (`\\ ; , \n`) et lignes pliées à 75 octets sans couper un caractère (`mb_strcut`).
- `scoutsunite_liens_pratiques( $section, $post_id )` : les liens « à mettre sur le frigo » et « Ajouter à mon calendrier » (un `details` avec `webcal://`, Google Agenda via `calendar.google.com/calendar/render?cid=` et le lien à copier), sous les blocs « Prochaine réunion d'une section », « Agenda complet » et « Détails de la réunion », états vides compris.

## Feuilles à imprimer (`inc/imprimer.php`)

- Sur `template_redirect`, une adresse portant `?imprimer` est servie par une page HTML autonome (sans en-tête ni pied du site, `noindex`), stylée par `assets/css/imprimer.css` (A4, `@page`, barre d'outils masquée à l'impression). Le PDF vient de la fenêtre d'impression du navigateur : aucune bibliothèque côté serveur.
- `/agenda/?section={slug}&imprimer&mois={1|3|6|12}` : programme. Une section d'âge reçoit aussi les activités `unite`, comme dans « Temps forts ». Sans section valide : tout l'agenda. Durée par défaut : 3 mois.
- `/agenda/{réunion}/?imprimer` : convocation d'une réunion (date en grand, horaire, lieu, notes complètes).
- Le pied de chaque feuille liste le staff de la section, ses contacts et l'adresse du local ; un champ vide de Réglages → Unité est omis, sans exemple « à compléter ».
- Liens : voir `scoutsunite_liens_pratiques()` ci-dessus, plus l'action de ligne « Convocation à imprimer » dans la liste des réunions (réunions publiées seulement).

## Staff et groupes WhatsApp (`inc/unite.php`, `inc/whatsapp.php`)

- Une ligne du staff : `Totem – Prénom (fonction) GSM`. `scoutsunite_unite_personne()` renvoie `[ totem, prénom, fonction, gsm ]` : la fonction est le texte entre parenthèses, le GSM le premier numéro commençant par `0` ou `+`. Les anciennes lignes « Totem – Prénom » ou « Totem, Prénom » donnent le même résultat qu'avant.
- Champs `whatsapp_{section}` (URL) pour chaque entrée de `scoutsunite_agenda_sections()`, y compris `unite` et `iama`.
- `scoutsunite_qr_svg( $texte, $label )` dessine le QR (niveau M, mode octet, marge de 4 modules) en un seul tracé SVG, avec la bibliothèque MIT de Kazuhiko Arase copiée dans `inc/lib/qrcode.php`, ses classes et constantes préfixées `SU_`.
- Bloc `scoutsunite/whatsapp` (attribut `section`, `''` = tous les groupes, sinon la section et l'unité), une carte `a.whatsapp__groupe` par groupe encodé. L'affiche du staff de section ajoute la carte de sa section ; les feuilles à imprimer, celles de la section et de l'unité.

## Installation et mise à jour

- **Activation** : `after_switch_theme` appelle `flush_rewrite_rules()` (après `scoutsunite_agenda_register()`), pour que `/agenda/` existe dès l'activation.
- **Mise à jour d'un site existant** : enregistrer une fois Réglages → Permaliens.
- **Menu** : ajouter un lien « Agenda » vers `get_post_type_archive_link( 'reunion' )`, avec `/agenda/` en repli.

## Vérifications attendues

- Une réunion sans section ou sans date ne peut pas être publiée.
- Publier une réunion avec « répéter jusqu'au » crée les copies hebdomadaires une seule fois, même si on l'enregistre de nouveau.
- Une réunion d'hier disparaît de l'accueil, de la page de section et de `/agenda/`. Une réunion d'aujourd'hui reste visible.
- `/agenda/?section=xyz`, avec une section inconnue, affiche toutes les réunions sans erreur.
- Cocher « Mettre en avant » sur une activité à venir la fait apparaître en grand sur l'accueil ; une fois sa date passée, la suivante prend sa place, puis la dernière actualité quand il n'en reste aucune.
- Les dates s'affichent en français et dans le bon fuseau (`wp_timezone()`), sans décalage d'un jour.
