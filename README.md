# Les Scouts – Unité

Thème WordPress (thème de bloc, Full Site Editing) pour le site d’une unité **Les Scouts asbl** : agenda des réunions, pages de section, staffs, inscriptions, contact et clan IAmA, le tout modifiable dans l’éditeur par des animateurs bénévoles, sans code.

Il reprend la charte graphique du [template des sites d’unité](https://github.com/lesscouts/template-unite) publié par Les Scouts asbl : bleu de la fédération, toppings, couleurs de section, polices Muli et Caveat Brush, logos.

**[Essayer le thème en ligne](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/guepe/les-scouts-unite/main/playground/demo.json)** (WordPress Playground, dans le navigateur, rien à installer) · **[Télécharger la dernière version](https://github.com/guepe/les-scouts-unite/releases/latest)**

L’accueil est un **panneau d’affichage** : le bleu de la fédération criblé de toppings, avec les affiches du moment épinglées dessus (prochaines réunions, activité à la une, inscriptions).

## Modes d’emploi

Deux guides pas à pas, à envoyer aux personnes qui tiennent le site :

- [Mode d’emploi pour les animateurs](docs/mode-emploi-animateurs.md) : les réunions, une réunion annulée, les documents et la page de sa section.
- [Mode d’emploi pour les chefs d’unité](docs/mode-emploi-chefs-unite.md) : les rôles, les comptes des animateurs, Réglages → Unité, les pages, les réglages et la mise à jour du thème.

## Installer le thème

1. Télécharge `les-scouts-unite.zip` depuis la [dernière version publiée](https://github.com/guepe/les-scouts-unite/releases/latest).
2. Dans WordPress : **Apparence → Thèmes → Ajouter → Téléverser un thème**, choisis le zip, puis **Activer**.
3. **Réglages → Général** : le **titre du site** est le nom de ton unité (par ex. `Unité 12XY Localité`) et le **slogan** sa ligne de présentation (par ex. `Unité scoute de Localité`). Le thème les affiche dans l’en-tête, le panneau d’accueil et le pied de page.
4. **Réglages → Unité** : les coordonnées de l’unité, encodées une seule fois (voir ci-dessous).
5. **Réglages → Permaliens** : « Titre de la publication ».

Il faut WordPress 6.5 ou plus récent.

## Créer les pages

Le menu pointe vers ces adresses. Crée les pages avec ces identifiants (slug) :

| Page | Slug | Composition à choisir à la création |
|---|---|---|
| Actualités | `actualites` | vide (puis **Réglages → Lecture** : page des articles) |
| Les sections | `sections` | « Page Les sections » |
| Baladins, Louveteaux, Éclaireurs, Pionniers | `baladins`, … (page parente : Les sections) | « Page de section : … » + modèle **Page pleine largeur (sans titre)** |
| Inscription | `inscription` | « Page Inscription » |
| Contact | `contact` | « Page Contact » |
| Documents | `documents` | « Page Documents » |
| Questions fréquentes | `questions-frequentes` | « Page Questions fréquentes » |
| IAmA – Anciens et amis | `iama` | « Page IAmA – Anciens et amis » |

Les pages Documents et Questions fréquentes apparaissent d’elles-mêmes dans le menu (sous-menu **Parents**) et dans le pied de page dès qu’elles sont publiées, à condition que leur identifiant soit exactement `documents` et `questions-frequentes`. Si l’en-tête a été modifié dans l’éditeur de site, il ne suit plus le thème : ajoute les liens à la main, ou rétablis l’en-tête (**Apparence → Éditeur → Compositions → En-tête → Réinitialiser**).

À la création d’une page, WordPress propose les compositions de la catégorie **Pages de l’unité**. L’accueil ne demande rien : le modèle `front-page` l’affiche toujours.

## Modifier le site

- **Accueil, en-tête, pied de page** : **Apparence → Éditeur → Modèles → Page d’accueil**. L’affiche d’inscription se modifie directement dans le panneau ; les réunions viennent de l’agenda et l’affiche blanche de droite montre l’activité mise en avant dans l’agenda (voir ci-dessous).
- **Actualités** : ce sont les articles WordPress. Les 4 derniers s’affichent sur l’accueil, le plus récent en grand avec son image mise en avant.
- **Styles de blocs** ajoutés (panneau latéral → Styles d’un bloc Groupe) : *Panneau*, *Affiche épinglée*, *Affiche épinglée verte*, *Ligne Baladins/Louveteaux/Éclaireurs/Pionniers*, *Bandeau* de chaque section, et *Manuscrit* pour les paragraphes.
- Les compositions de la catégorie **Unité scoute** (panneau, règle des âges, actualités, infos pratiques) peuvent être insérées n’importe où.

## Les droits d’accès

Le thème ajoute deux rôles (**Comptes → Ajouter** ou **Comptes → Tous les comptes**, puis « Rôle ») :

- **Animateur** : gère les réunions de sa ou ses sections (créer, modifier, publier, supprimer) et modifie les textes et images de la page de sa section, sans pouvoir en changer la mise en page. Il ne voit et ne touche rien d’autre. Un administrateur coche ses sections dans son profil (« Sections de l’animateur ») ; la section « IAmA » peut aussi être attribuée, au responsable du clan par exemple.
- **Staff d’unité** : gère tout le contenu (réunions de toutes les sections, pages, actualités) et **Réglages → Unité**, sans accès aux extensions, au thème ni aux comptes.

Les limites sont vérifiées par WordPress à l’enregistrement, pas seulement cachées dans l’interface.

## L’agenda des réunions

Les réunions s’encodent dans **Agenda → Ajouter** (menu de gauche de l’administration). Dans le panneau **Quand et où ?** à droite : section, date, début, fin, lieu. Le titre est facultatif (« Réunion » par défaut ; mets « Hike », « Week-end »… pour une activité spéciale) et le texte sert de notes pour les parents (à emporter, rendez-vous).

- **Type : Réunion, Week-end, Camp ou Réunion spéciale** : pour un week-end ou un camp, indique aussi la date de fin (« Jusqu’au »). L’activité s’affiche alors « du 13 au 15 novembre » avec une étiquette « Week-end » ou « Camp », reste visible jusqu’à son dernier jour, et apparaît dans le bloc **Les temps forts** en bas de la page de sa section, comme une réunion spéciale (Halloween, fête de section…) (avec ses notes complètes : prix, matériel…). Une activité « Toute l’unité » apparaît sur les pages de toutes les sections.
- **Section « IAmA »** : pour les activités du clan des anciens et amis (souper, chantier…). Elles apparaissent sur la page IAmA et dans l’agenda, pas dans les « Prochaines réunions » des sections d’âge.
- **Mettre en avant sur l’accueil** : coche cette case pour les grandes activités (week-end d’unité, souper, fête…). La prochaine activité cochée s’affiche en grand sur l’affiche blanche en haut de l’accueil, les suivantes en dessous, jusqu’à leur date. Sans activité cochée à venir, l’affiche montre la dernière actualité.
- **Répéter chaque semaine jusqu’au** : à la publication, une copie est créée chaque semaine jusqu’à cette date. Supprime ensuite les semaines de congé dans **Agenda → Toutes les réunions**.
- **Mise à jour automatique** : le panneau de l’accueil montre la prochaine réunion de chaque section, chaque page de section montre la sienne (avec les dates suivantes), et **/agenda/** liste tout, mois par mois, avec un filtre par section. Les réunions passées disparaissent d’elles-mêmes.
- **Documents à télécharger** : dans **Documents → Ajouter**, donne un titre, choisis le fichier dans la médiathèque (un PDF de préférence), indique pour qui il est (une section ou toute l’unité) et, si tu veux, une ligne d’explication. Le document s’affiche sous « Les documents » sur la page de la section (un document de toute l’unité, sur chaque page de section) et sur la page Documents. Le champ « Ordre » règle l’ordre d’affichage. Un animateur ne gère que les documents de ses sections.
- **Questions fréquentes** : crée la page à partir de la composition « Page Questions fréquentes », puis remplace les exemples surlignés. Chaque question est un bloc **Détails** : la question en titre, la réponse dedans. Pour ajouter une question, duplique un bloc Détails.
- **Réunion annulée** : coche « Réunion annulée » dans le panneau « Quand et où ? », avec si tu veux un mot d’explication (« Local indisponible, rendez-vous samedi prochain »). Ne supprime pas la réunion : elle reste affichée jusqu’à sa date, barrée et marquée « Annulée », partout où les parents la cherchent, y compris dans leur calendrier.
- **Bon à savoir** : dans **Réglages → Unité**, chaque section a son champ « Bon à savoir », avec une info par ligne : horaire habituel, lieu, uniforme, ce qu’il faut emporter. Un mot suivi de deux-points passe en gras (« Uniforme : pull de la section, foulard »). L’encadré s’affiche sous la prochaine réunion de la page de section et sur les feuilles à imprimer.
- **Dans le calendrier du téléphone** : « Ajouter à mon calendrier », sur chaque page de section et sous l’agenda, propose un abonnement (iPhone, Mac ou Outlook ; Google Agenda ; ou le lien à copier). Le calendrier du parent reçoit les réunions de la section et celles de toute l’unité, et suit les changements tout seul (Google rafraîchit toutes les 12 à 24 heures). Sur la page d’une réunion, le lien ajoute cette seule réunion.
- **Feuilles à imprimer** (pour le frigo) : « Le programme à mettre sur le frigo », sur chaque page de section et sous l’agenda filtré, ouvre une page A4 avec le logo de la section, les réunions des prochains mois (1, 3, 6 ou 12 mois au choix), leurs notes et le staff de Réglages → Unité. « La convocation à mettre sur le frigo », sur la page d’une réunion et dans **Agenda → Toutes les réunions**, fait la même chose pour une seule réunion, en grand. Le bouton « Imprimer ou enregistrer en PDF » ouvre la fenêtre d’impression du navigateur : choisis « Enregistrer au format PDF » pour obtenir le fichier à envoyer aux parents.
- **Groupes WhatsApp** : colle le lien d’invitation de chaque groupe (une section, toute l’unité, IAmA) dans **Réglages → Unité**. Le site en fait un QR code, affiché dans l’affiche du staff de la section, sur la page Contact et sur les feuilles à imprimer. Toute personne qui a le lien peut rejoindre le groupe : garde l’approbation des nouveaux membres activée dans WhatsApp. Une page Contact créée avant la version 2.8.0 n’a pas encore le bloc : ajoute le bloc **Groupes WhatsApp** dans l’éditeur.
- Les blocs **Prochaines réunions**, **À la une**, **Prochaine réunion d’une section**, **Temps forts de la section** et **Agenda complet** peuvent être insérés n’importe où dans l’éditeur.

### Mettre à jour un site déjà installé

1. Téléverse le nouveau zip (**Apparence → Thèmes → Ajouter → Téléverser**, puis **Remplacer la version installée**).
2. **Réglages → Permaliens → Enregistrer** (sans rien changer) pour que l’adresse /agenda/ existe.
3. Si tu as déjà modifié la page d’accueil dans l’éditeur, elle garde les anciennes lignes écrites à la main : remplace-les par le bloc **Prochaines réunions**, ou fais **⋮ → Réinitialiser** sur le modèle **Page d’accueil**.
4. Les pages de section déjà créées gardent leur ancienne affiche : remplace la date écrite à la main par le bloc **Prochaine réunion d’une section** (choisis la section dans le panneau de droite).
5. Si tu as modifié l’en-tête, ajoute toi-même le lien **Agenda** au menu.

## À compléter avant la mise en ligne

Tout ce qui est surligné en orange pâle est un exemple à remplacer.

### Les coordonnées de l’unité : Réglages → Unité

L’adresse du local, le ou les responsables d’unité (nom, téléphone), l’e-mail du staff d’unité, le staff de chaque section (le chef ou la cheffe, puis les animateurs, un par ligne sous la forme « Totem – Prénom », avec si tu veux la fonction entre parenthèses et le GSM : « Castor – Marie (santé) 0470 12 34 56 ») et son contact (e-mail, GSM ou les deux), les liens des groupes WhatsApp, les tarifs de cotisation (un par ligne, par ex. « 80 € pour le 1er enfant »), le responsable et l’e-mail du clan IAmA, le numéro de compte, les places disponibles et les liens des formulaires d’inscription et de contact (Google Forms, extension de formulaire…) s’encodent **une seule fois** dans **Réglages → Unité**. Ils s’affichent d’eux-mêmes dans le pied de page, l’affiche « Le staff » de chaque page de section (un badge par animateur, le chef en tête), les infos pratiques de l’accueil, l’affiche d’inscription, la page Contact et la page Inscription, et le lien « Itinéraire » se calcule à partir de l’adresse. Dans l’éditeur, ces blocs montrent la vraie valeur mais ne se modifient pas sur place : on change la valeur dans les réglages. Un champ vide garde son exemple surligné.

Ces blocs sont liés aux réglages dans les compositions du thème. Une page créée avant cette fonction (ou un bloc recopié à la main) garde son texte figé : recrée-la à partir de sa composition (« Page Contact », « Page Inscription ») pour profiter des réglages.

### Le reste, directement dans les pages

- la phrase d’accroche manuscrite du pied de page (« Toujours prêts, de 6 à 18 ans. ») si tu veux la personnaliser ;
- les photos : une seule photo d’exemple est fournie (celle du dépôt de la fédération).

## Développer

### Essayer en local

Avec [WordPress Playground](https://wordpress.github.io/wordpress-playground/), sans rien installer d’autre que Node, depuis la racine du dépôt :

```sh
npx @wp-playground/cli@latest server --port=9400 \
  --mount=.:/wordpress/wp-content/themes/les-scouts-unite \
  --blueprint=./blueprint.json
```

Puis ouvre http://127.0.0.1:9400 (identifiant `admin`, mot de passe `password`). Le contenu d’exemple (pages, articles, réunions) vient de `playground/contenu-demo.php`, partagé avec la démo en ligne (`playground/demo.json`, qui installe le thème depuis GitHub).

### Publier une version

1. Monte `Version:` dans `style.css` (par ex. `2.8.0`) et note les changements dans `CHANGELOG.md`.
2. Commite, puis crée et pousse le tag correspondant :
   ```sh
   git tag v2.8.0 && git push origin main v2.8.0
   ```
3. L’action GitHub [`release`](.github/workflows/release.yml) vérifie que le tag et `style.css` portent la même version, construit `les-scouts-unite.zip` (sans la doc ni les fichiers de développement, voir `.gitattributes`) et publie la version avec le zip.

Pour fabriquer le zip à la main : `git archive --format=zip --prefix=les-scouts-unite/ -o les-scouts-unite.zip HEAD`.

### Documentation technique

- [`docs/agenda-module.md`](docs/agenda-module.md) : l’agenda et les droits, de quoi les reproduire ailleurs.
- [`DESIGN.md`](DESIGN.md) : le système graphique (couleurs, typographie, composants).
- [`PRODUCT.md`](PRODUCT.md) : à qui sert le site et ce qu’il doit permettre.

## Polices

La charte recommande Housepaint, mais elle n’a pas de lettres accentuées : les titres sont donc en Muli Black, et Caveat Brush sert pour les annotations manuscrites des affiches.

## Origine et licences

Ce thème est né dans un fork du [template des sites d’unité](https://github.com/lesscouts/template-unite) de Les Scouts asbl, dont il reprend la charte graphique ; il vit désormais dans son propre dépôt. Le code est sous licence MIT (voir [`LICENSE`](LICENSE)). Les logos, toppings et éléments de la charte restent la propriété de Les Scouts asbl. Muli et Caveat Brush sont sous licence SIL Open Font License. Les QR codes sont dessinés par [QR Code for PHP](https://github.com/kazuhikoarase/qrcode-generator) de Kazuhiko Arase (licence MIT), copié dans `inc/lib/qrcode.php`.
