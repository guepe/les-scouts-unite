# Thème WordPress – Les Scouts – Unité

Thème de bloc (Full Site Editing) pour le site d’une unité **Les Scouts asbl**, construit sur la charte graphique Les Scouts asbl de ce dépôt : bleu de la fédération, toppings, couleurs de section, polices Muli et Caveat Brush, logos.

L’accueil est un **panneau d’affichage** : le bleu de la fédération criblé de toppings, avec les affiches du moment épinglées dessus (prochaines réunions, activité à la une, inscriptions).

## Installer le thème

1. Crée le zip du thème :
   ```sh
   cd wordpress && zip -r les-scouts-unite.zip les-scouts-unite
   ```
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
| IAmA – Anciens et amis | `iama` | « Page IAmA – Anciens et amis » |

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

L’adresse du local, le ou les responsables d’unité (nom, téléphone), l’e-mail du staff d’unité, le staff de chaque section (le chef ou la cheffe, puis les animateurs, un par ligne sous la forme « Totem – Prénom ») et son e-mail, les tarifs de cotisation (un par ligne, par ex. « 80 € pour le 1er enfant »), le responsable et l’e-mail du clan IAmA, le numéro de compte, les places disponibles et les liens des formulaires d’inscription et de contact (Google Forms, extension de formulaire…) s’encodent **une seule fois** dans **Réglages → Unité**. Ils s’affichent d’eux-mêmes dans le pied de page, l’affiche « Le staff » de chaque page de section (un badge par animateur, le chef en tête), les infos pratiques de l’accueil, l’affiche d’inscription, la page Contact et la page Inscription, et le lien « Itinéraire » se calcule à partir de l’adresse. Dans l’éditeur, ces blocs montrent la vraie valeur mais ne se modifient pas sur place : on change la valeur dans les réglages. Un champ vide garde son exemple surligné.

Ces blocs sont liés aux réglages dans les compositions du thème. Une page créée avant cette fonction (ou un bloc recopié à la main) garde son texte figé : recrée-la à partir de sa composition (« Page Contact », « Page Inscription ») pour profiter des réglages.

### Le reste, directement dans les pages

- la phrase d’accroche manuscrite du pied de page (« Toujours prêts, de 6 à 18 ans. ») si tu veux la personnaliser ;
- les photos : une seule photo d’exemple est fournie (celle du dépôt de la fédération).

## Essayer en local

Avec [WordPress Playground](https://wordpress.github.io/wordpress-playground/), sans rien installer d’autre que Node :

```sh
cd wordpress
npx @wp-playground/cli@latest server --port=9400 \
  --mount=./les-scouts-unite:/wordpress/wp-content/themes/les-scouts-unite \
  --blueprint=./blueprint.json
```

Puis ouvre http://127.0.0.1:9400 (identifiant `admin`, mot de passe `password`). Le blueprint crée les pages, quatre articles et des réunions d’exemple.

## Polices

La charte recommande Housepaint, mais elle n’a pas de lettres accentuées : les titres sont donc en Muli Black, et Caveat Brush sert pour les annotations manuscrites des affiches.
