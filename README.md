# Les Scouts – Unité

Thème WordPress (thème de bloc, Full Site Editing) pour le site d’une unité **Les Scouts asbl** : agenda des réunions, pages de section, staffs, inscriptions, contact et clan IAmA, le tout modifiable dans l’éditeur par des animateurs bénévoles, sans code.

Il reprend la charte graphique du [template des sites d’unité](https://github.com/lesscouts/template-unite) publié par Les Scouts asbl : bleu de la fédération, toppings, couleurs de section, polices Muli et Caveat Brush, logos.

**[Essayer le thème en ligne](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/guepe/les-scouts-unite/main/playground/demo.json)** (WordPress Playground, dans le navigateur, rien à installer) · **[Télécharger la dernière version](https://github.com/guepe/les-scouts-unite/releases/latest)**

L’accueil est un **panneau d’affichage** : le bleu de la fédération criblé de toppings, avec les affiches du moment épinglées dessus (prochaines réunions, activité à la une, inscriptions).

## Modes d’emploi

Des guides pas à pas, à envoyer aux personnes qui tiennent le site :

- [Le site en 9 fiches, pour les animateurs](docs/fiches-animateurs/) : un geste par fiche, avec les vrais écrans, à lire sur un téléphone ou à envoyer en images ([PDF](docs/fiches-animateurs/fiches-animateurs.pdf)).
- [Mode d’emploi pour les animateurs](docs/mode-emploi-animateurs.md) : le même contenu en texte, plus détaillé (les réunions, une réunion annulée, les documents et la page de sa section).
- [Le site en 10 fiches, pour le staff d’unité](docs/fiches-chefs-unite/) : la même chose pour les chefs d’unité, des comptes des animateurs à la mise à jour du thème ([PDF](docs/fiches-chefs-unite/fiches-chefs-unite.pdf)).
- [Mode d’emploi pour les chefs d’unité](docs/mode-emploi-chefs-unite.md) : les rôles, les comptes des animateurs, Réglages → Unité, les pages, les réglages et la mise à jour du thème.

## Installer le thème

Depuis la version 3.0, le site tient en deux morceaux, publiés ensemble : le **thème** (l’apparence : couleurs, pages, compositions) et son **extension** (l’agenda, les documents, les droits des animateurs, Réglages → Unité, le calendrier du téléphone, les feuilles à imprimer et WhatsApp). Il faut les deux.

1. Télécharge `les-scouts-unite.zip` et `les-scouts-unite-extension.zip` depuis la [dernière version publiée](https://github.com/guepe/les-scouts-unite/releases/latest).
2. Dans WordPress : **Extensions → Ajouter → Téléverser une extension**, choisis `les-scouts-unite-extension.zip`, puis **Activer**.
3. **Apparence → Thèmes → Ajouter → Téléverser un thème**, choisis `les-scouts-unite.zip`, puis **Activer**.
4. **Réglages → Général** : le **titre du site** est le nom de ton unité (par ex. `Unité 12XY Localité`) et le **slogan** sa ligne de présentation (par ex. `Unité scoute de Localité`). Le thème les affiche dans l’en-tête, le panneau d’accueil et le pied de page.
5. **Réglages → Unité** : les coordonnées de l’unité, encodées une seule fois (voir ci-dessous).
6. **Réglages → Permaliens** : « Titre de la publication ».

Il faut WordPress 6.6 ou plus récent, avec PHP 7.4 ou plus.

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
- **Staff d’unité** : gère tout le contenu (réunions de toutes les sections, pages, actualités), les fiches du staff et **Réglages → Unité**, sans accès aux extensions, au thème ni aux comptes.

Les limites sont vérifiées par WordPress à l’enregistrement, pas seulement cachées dans l’interface.

## L’agenda des réunions

Les réunions s’encodent dans **Agenda → Ajouter** (menu de gauche de l’administration). Dans le panneau **Quand et où ?** à droite : section, date, début, fin, lieu. Le titre est facultatif (« Réunion » par défaut ; mets « Hike », « Week-end »… pour une activité spéciale) et le texte sert de notes pour les parents (à emporter, rendez-vous).

- **Type : Réunion, Week-end, Camp ou Réunion spéciale** : pour un week-end ou un camp, indique aussi la date de fin (« Jusqu’au »). L’activité s’affiche alors « du 13 au 15 novembre » avec une étiquette « Week-end » ou « Camp », reste visible jusqu’à son dernier jour, et apparaît dans le bloc **Les temps forts** en bas de la page de sa section, comme une réunion spéciale (Halloween, fête de section…) (avec ses notes complètes : prix, matériel…). Une activité « Toute l’unité » apparaît sur les pages de toutes les sections.
- **Section « IAmA »** : pour les activités du clan des anciens et amis (souper, chantier…). Elles apparaissent sur la page IAmA et dans l’agenda, pas dans les « Prochaines réunions » des sections d’âge.
- **Mettre en avant sur l’accueil** : coche cette case pour les grandes activités (week-end d’unité, souper, fête…). La prochaine activité cochée s’affiche en grand sur l’affiche blanche en haut de l’accueil, les suivantes en dessous, jusqu’à leur date. Sans activité cochée à venir, l’affiche montre la dernière actualité.
- **Répéter chaque semaine jusqu’au** : à la publication, une copie est créée chaque semaine jusqu’à cette date. Supprime ensuite les semaines de congé dans **Agenda → Toutes les réunions**.
- **Mise à jour automatique** : le panneau de l’accueil montre la prochaine réunion de chaque section, chaque page de section montre la sienne (avec les dates suivantes), et **/agenda/** liste tout, mois par mois, avec un filtre par section. Les boutons s’additionnent : un parent de deux enfants touche leurs deux sections et les voit ensemble (`/agenda/?section=baladins,louveteaux`) ; le calendrier et la feuille pour le frigo suivent le même choix. Les réunions passées disparaissent d’elles-mêmes.
- **Documents à télécharger** : dans **Documents → Ajouter**, donne un titre, choisis le fichier dans la médiathèque (un PDF de préférence), indique pour qui il est (une section ou toute l’unité) et, si tu veux, une ligne d’explication. Le document s’affiche sous « Les documents » sur la page de la section (un document de toute l’unité, sur chaque page de section) et sur la page Documents. Le champ « Ordre » règle l’ordre d’affichage. Un animateur ne gère que les documents de ses sections.
- **Questions fréquentes** : crée la page à partir de la composition « Page Questions fréquentes », puis remplace les exemples surlignés. Chaque question est un bloc **Détails** : la question en titre, la réponse dedans. Pour ajouter une question, duplique un bloc Détails.
- **Réunion annulée** : coche « Réunion annulée » dans le panneau « Quand et où ? », avec si tu veux un mot d’explication (« Local indisponible, rendez-vous samedi prochain »). Ne supprime pas la réunion : elle reste affichée jusqu’à sa date, barrée et marquée « Annulée », partout où les parents la cherchent, y compris dans leur calendrier.
- **Bon à savoir** : dans **Réglages → Unité**, chaque section a son champ « Bon à savoir », avec une info par ligne : horaire habituel, lieu, uniforme, ce qu’il faut emporter. Un mot suivi de deux-points passe en gras (« Uniforme : pull de la section, foulard »). L’encadré s’affiche sous la prochaine réunion de la page de section et sur les feuilles à imprimer.
- **Dans le calendrier du téléphone** : « Ajouter à mon calendrier », sur chaque page de section et sous l’agenda, propose un abonnement (iPhone, Mac ou Outlook ; Google Agenda ; ou le lien à copier). Le calendrier du parent reçoit les réunions de la section (ou des sections choisies dans l’agenda) et celles de toute l’unité, et suit les changements tout seul (Google rafraîchit toutes les 12 à 24 heures). Sur la page d’une réunion, le lien ajoute cette seule réunion.
- **Feuilles à imprimer** (pour le frigo) : « Le programme à mettre sur le frigo », sur chaque page de section et sous l’agenda filtré, ouvre une page A4 avec le logo de la section (avec plusieurs sections : une feuille commune, chaque réunion avec le nom de sa section), les réunions des prochains mois (1, 3, 6 ou 12 mois au choix), leurs notes et le staff de la section (ses fiches). « La convocation à mettre sur le frigo », sur la page d’une réunion et dans **Agenda → Toutes les réunions**, fait la même chose pour une seule réunion, en grand. Le bouton « Imprimer ou enregistrer en PDF » ouvre la fenêtre d’impression du navigateur : choisis « Enregistrer au format PDF » pour obtenir le fichier à envoyer aux parents.
- **Groupes WhatsApp** : colle le lien d’invitation de chaque groupe (une section, toute l’unité, IAmA) dans **Réglages → Unité**. Le site en fait un QR code, affiché dans l’affiche du staff de la section, sur la page Contact et sur les feuilles à imprimer. Toute personne qui a le lien peut rejoindre le groupe : garde l’approbation des nouveaux membres activée dans WhatsApp. Une page Contact créée avant la version 2.8.0 n’a pas encore le bloc : ajoute le bloc **Groupes WhatsApp** dans l’éditeur.
- **Le staff et les cartes d’animateur** : dans **Staff → Ajouter**, une fiche par personne (sections, staff d’unité, clan IAmA) : prénom, nom, totem, quali, totem de meute pour les Louveteaux, chef ou cheffe, fonction, GSM et photo. L’affiche « Le staff » de la page de section, la page Contact et les feuilles pour le frigo en tirent le totem (chez les Louveteaux, le totem de meute s’il est rempli), le prénom, la fonction et le GSM. Le nom, le quali et la photo ne servent qu’à la carte : les fiches ne sont pas en ligne (ni page, ni API) et la photo est rangée hors de la médiathèque, visible seulement des personnes connectées qui ont droit à la fiche. « Imprimer la carte » (sous chaque fiche) ou les liens « Imprimer les cartes » en haut de la liste ouvrent des planches A4 de 10 cartes (8,5 × 5,4 cm), à imprimer en recto-verso retourné sur le bord long, à 100 %. Le logo de l’unité (Réglages → Unité) s’imprime sous celui de la section. Au verso, le QR code du groupe WhatsApp de la section ; pour le staff d’unité, celui du formulaire d’inscription. Un animateur dont le compte est relié à sa fiche (champ « Compte du site ») la modifie lui-même ; il ne change ni sa section ni son rôle de chef. Quand quelqu’un quitte le staff, coche « Ancien » plutôt que de supprimer la fiche.
- Les blocs **Prochaines réunions**, **À la une**, **Prochaine réunion d’une section**, **Temps forts de la section** et **Agenda complet** peuvent être insérés n’importe où dans l’éditeur.

### Mettre à jour un site déjà installé

**Passer d’une version 2.x à la 3.0** (une seule fois) : installe d’abord l’extension, puis le thème.

1. **Extensions → Ajouter → Téléverser une extension** : `les-scouts-unite-extension.zip`, puis **Activer**. Tant que le thème est en 2.x, l’extension attend sans rien changer (un message le rappelle en haut de l’administration).
2. **Apparence → Thèmes → Ajouter → Téléverser** : `les-scouts-unite.zip`, puis **Remplacer la version installée**. L’extension prend le relais : les réunions, les documents, les comptes des animateurs et Réglages → Unité restent tels quels.

Dans l’autre ordre, rien ne casse non plus : entre les deux, l’agenda disparaît du site et un message demande d’installer l’extension.

Si, juste après le remplacement du thème, le site affiche une erreur « Failed opening required … `inc/agenda.php` », c’est le cache PHP de l’hébergeur (OPcache) : il exécute encore l’ancien thème, alors que ses fichiers ont déjà été remplacés. Vide ce cache dans le panneau de l’hébergeur (« Vider OPcache », « Redémarrer PHP », ou changer de version de PHP puis revenir). Sans ce bouton, réenregistre `wp-content/themes/les-scouts-unite/functions.php` tel quel (FTP ou gestionnaire de fichiers). En dernier recours, renomme le dossier du thème : WordPress repasse sur un thème par défaut le temps de vider le cache.

**Passer à la 3.1** : les lignes du staff de Réglages → Unité deviennent des fiches dans **Staff**. Complète-les (nom, quali, il ou elle, photo, totem de meute pour les Louveteaux) avant d’imprimer les cartes.

**Les versions suivantes** : remplace les deux zips de la même version (l’extension dans **Extensions → Ajouter → Téléverser**, le thème dans **Apparence → Thèmes → Ajouter → Téléverser**, chaque fois **Remplacer la version installée**).

Pour un site qui vient de l’ancien thème `scouts-11om-magnee` (versions 1.x), en plus :

1. Installe l’extension et le thème comme ci-dessus.
2. **Réglages → Permaliens → Enregistrer** (sans rien changer) pour que l’adresse /agenda/ existe.
3. Si tu as déjà modifié la page d’accueil dans l’éditeur, elle garde les anciennes lignes écrites à la main : remplace-les par le bloc **Prochaines réunions**, ou fais **⋮ → Réinitialiser** sur le modèle **Page d’accueil**.
4. Les pages de section déjà créées gardent leur ancienne affiche : remplace la date écrite à la main par le bloc **Prochaine réunion d’une section** (choisis la section dans le panneau de droite).
5. Si tu as modifié l’en-tête, ajoute toi-même le lien **Agenda** au menu.

## Les photos de camp

Depuis la 3.5, **Photos de camp** dans le menu WordPress ouvre un espace adapté au téléphone. Depuis la 3.5, une section peut créer plusieurs albums par année : donne un nom à chacun, par exemple « Grand camp » et « Week-end de printemps ». Chaque album a sa propre clé. Les animateurs y sélectionnent les photos de leurs sections et les publient directement avec « Publier les photos ». Ils gèrent aussi l’accès de leurs albums et transmettent aux parents un lien avec une clé commune à cet album. Les parents n’ont pas de compte à créer. La mise à jour conserve les photos, adresses et clés des albums existants.

L’espace privé existe à **`/photos/`**, sans page WordPress. Depuis la 3.5.1, une page publique **`/photos-de-camp/`** est créée une seule fois pour présenter les albums aux familles. L’accueil et les quatre pages de section y mènent automatiquement, y compris avec les anciennes compositions. Cette page n’affiche ni photo privée, ni adresse d’album, ni clé. Une page existante du même nom est respectée ; une suppression ou une dépublication volontaire est conservée. Le bloc **Photos de camp** permet aussi d’ajouter un encart sur une autre page ; le bloc **Présentation des photos de camp** compose la page dédiée. Le catalogue des parents ne contient que les albums déjà ouverts avec leurs liens ; la clé d’une année ne donne pas accès aux précédentes.

La galerie affiche 24 miniatures par page. Toucher une photo ouvre une visionneuse qui parcourt tout l’album avec les boutons **Précédente** et **Suivante**, les flèches du clavier ou un balayage horizontal. Si un tag est choisi, la navigation reste dans ce moment. Le lien discret **Quitter définitivement cet album**, en bas de l’album (avec une confirmation), efface son accès sur le navigateur ; il faut rouvrir le lien reçu du staff pour revenir. Il ne ferme pas l’album pour les autres parents.

Plusieurs comptes animateurs rattachés à la même section peuvent alimenter et gérer le même album. Des **tags libres**, par exemple « Petit déjeuner », « Dîner » ou « Grand jeu », s’ajoutent dès l’envoi ou sur une sélection existante. La galerie se filtre par tag ; une photo peut en avoir plusieurs, et un tag peut être un nombre (« 2024 »). Les tags des photos à vérifier ne sont pas montrés aux parents.

**Exporter pour préparer un livre photo** télécharge un ZIP des photos publiées de l’album, ou du tag affiché, avec un index `photos.json` contenant leurs tags et légendes. Cet export complet reste réservé aux animateurs de la section et au staff. Depuis la 3.5.1, les parents peuvent aussi exporter les références explicitement marquées dans **Ma sélection**. Tous les exports exigent l’extension PHP ZIP, sont préparés dans le dossier privé et effacés après téléchargement. Il contient les versions optimisées ; la mise en page du livre, le choix d’un imprimeur et les commandes restent à définir.

### Préparer le stockage privé (administrateur)

Le module crée par défaut un dossier `scoutsunite-prive-…` à côté du dossier WordPress, **hors de la racine web du serveur**. Si cet emplacement est public ou inaccessible, il refuse les envois et l’accès parent. Aucun repli vers la médiathèque ou un dossier protégé seulement par `.htaccess`.

Si nécessaire, demande à l’hébergeur de créer un dossier privé accessible en écriture par PHP, puis ajoute son chemin réel dans `wp-config.php`, avant le chargement de WordPress. Exemple de chemin à adapter, **pas un dossier dans `public_html`, `www` ou `htdocs`** :

```php
define( 'SCOUTSUNITE_PHOTOS_DOSSIER', '/home/ton-compte/photos-privees' );
```

Le serveur doit transmettre correctement `DOCUMENT_ROOT`, y compris si WordPress est dans un sous-dossier. Seules les tâches sans requête web (WP-CLI, cron du serveur) s’en passent, pour que le nettoyage quotidien tourne ; l’emplacement reste contrôlé à chaque visite. Vérifie aussi qu’aucun alias du serveur ne sert ce dossier par HTTP. HTTPS est obligatoire, sauf sur `localhost` ou `127.0.0.1` pour les essais. Derrière un proxy HTTPS, la configuration habituelle de WordPress doit lui permettre de reconnaître la connexion sécurisée.

**Sélection parent et demande privée (3.5.1)** : les cœurs mémorisent au plus 1 000 références par album dans `localStorage`, jusqu’à effacement volontaire, sans image, clé ou coordonnées. La sélection traverse les pages et les visites sur ce navigateur, sans synchronisation entre appareils. Le parent peut tout marquer dans l’album, exporter son choix ou transmettre nom, e-mail et message au staff. Les références sont revérifiées côté serveur : accès, album, publication et fichiers encore présents. Un nonce est lié à la session parent ; cinq demandes et cinq exports au plus sur dix minutes par session limitent les répétitions. Une même demande renvoyée n’est pas créée deux fois.

La table privée `scoutsunite_photos_demandes` conserve les coordonnées, le message et les références pour les animateurs de la section et le staff d’unité. Aucune route REST publique, aucun e-mail automatique, paiement ou appel à un imprimeur. Le staff consulte les demandes dans chaque album, exporte les photos encore disponibles et marque la demande comme traitée, ou l’efface. Le nettoyage quotidien efface les demandes traitées depuis 90 jours, pas celles en attente. Supprimer un album efface aussi ses demandes. Les sauvegardes peuvent encore contenir les coordonnées : prévoir leur durée avec l’hébergeur.

La visionneuse reste en JavaScript, y compris après l’ouverture d’un lien direct vers une photo : image conservée pendant le chargement, fondu court et une seule photo suivante préparée en mémoire. Les droits sont revérifiés à chaque changement. Sans JavaScript, les liens individuels restent utilisables, sans sélection persistante.

**Cache de l’hébergeur ou du CDN** : exclure `/photos/`, ses sous-adresses et les requêtes contenant `su_photos=1`. Le module envoie `private, no-store`, sans ressources tierces ni appel à `wp_head`/`wp_footer` ; un cache placé avant PHP doit respecter ces exclusions.

JPEG, PNG et WebP sont acceptés, à concurrence de la limite PHP du site et de 20 Mo par fichier, 40 mégapixels et 12 000 pixels de côté. Le serveur réencode la photo en JPEG, applique son orientation, retire les métadonnées et crée une miniature ; il garde au plus 2 560 pixels pour le téléchargement. Les originaux restent sur les téléphones. Pour HEIC, l’hébergeur doit fournir Imagick avec le décodeur HEIC/libheif ; la conversion est locale et fonctionne aussi avec WordPress 6.6 quand cette bibliothèque est disponible. Sinon, le message propose une version JPEG, sans transfert vers un convertisseur externe.

Quotas par défaut : **1 Go par album et 5 Go au total**, miniatures comprises, vérifiés côté serveur sous un verrou atomique en base. Ils peuvent être adaptés dans `wp-config.php` :

```php
define( 'SCOUTSUNITE_PHOTOS_QUOTA_ALBUM', 2 * 1024 * 1024 * 1024 );
define( 'SCOUTSUNITE_PHOTOS_QUOTA_TOTAL', 10 * 1024 * 1024 * 1024 );
```

### Partager et conserver

Les nouveaux albums sont prêts à recevoir des photos et disposent immédiatement d’un lien privé. L’animateur ouvre **Partager l’album et gérer l’accès** pour copier le message ou retrouver le QR à tout moment. Les parents n’ont accès qu’aux photos publiées et seulement avec la clé de cet album. Le staff d’unité peut gérer les albums de toutes les sections.

**Fermer** l’accès bloque les parents et supprime les sessions en cours. **Rouvrir** conserve le même lien : les parents peuvent l’utiliser à nouveau, mais les anciens cookies ne suffisent plus. **Renouveler le lien des parents** change la clé et coupe tous les anciens accès ; il faut transmettre le nouveau message.

Pour rendre le lien retrouvable sans stocker de clé en clair, les nouvelles clés de 144 bits sont dérivées par HMAC-SHA-256 des secrets WordPress, de l’adresse unique de l’album et de la version de son accès. La base ne conserve que leur empreinte de validation. Le lien et le QR ne sont rendus qu’après vérification des droits de l’animateur sur la section. Un changement des secrets WordPress peut nécessiter un renouvellement du lien.

**Mise à jour depuis les essais 3.3** : les anciens liens aléatoires restent valables. Leur empreinte ne permet pas de reconstruire leur QR : renouvelle le lien une fois, puis transmets le nouveau message aux parents. Les albums déjà fermés restent fermés et les anciennes photos « À vérifier » restent privées. Sélectionne-les puis utilise **Classer ou remettre en ligne la sélection → Remettre la sélection en ligne** pour les publier volontairement. Une photo retirée ne sera jamais republiée par un simple nouvel envoi du même fichier.

Une session parent dure sept jours au plus. Une date facultative peut fermer l’accès après un jour choisi. **Archivé** conserve la lecture parent et ferme les envois ; **Fermé** réserve la lecture aux animateurs autorisés, au staff d’unité et aux administrateurs. Aucune suppression automatique des albums n’est activée : la durée de conservation reste un choix de l’unité. Les actions de gestion sont journalisées 90 jours, sans suivi individuel des parents.

Avec une clé commune, toute personne recevant le lien peut ouvrir l’album. On ne peut ni révoquer une seule famille ni rappeler une photo déjà téléchargée. Vérifie les autorisations de diffusion avant publication et utilise **Retirer de l’album parent** si une photo doit disparaître.

Les animateurs autorisés peuvent aussi toucher la **poubelle à côté d’une photo**, puis confirmer sa suppression définitive ; la sélection multiple reste disponible. Les administrateurs et le staff d’unité peuvent supprimer tout l’album : ouvre **Supprimer l’album**, écris **SUPPRIMER**, puis confirme. Un animateur ne peut pas supprimer l’album complet. Le futur parcours de demande de retrait pour les parents reste à définir ; aucun lien de demande n’est affiché dans la galerie.

### Sauvegarder et restaurer

Sauvegarde le dossier privé **et** les tables `scoutsunite_photos_*` de cette installation, avec les protections de tes autres sauvegardes. Désactiver l’extension ne détruit pas les albums et ne rend pas les fichiers publics. Une suppression dans l’interface efface les deux variantes et les données concernées sur le site, mais n’efface pas rétrospectivement les sauvegardes.

Avant de rouvrir un site restauré, ferme les albums, réapplique les retraits intervenus depuis la sauvegarde puis crée de nouvelles clés : une sauvegarde ancienne peut contenir des photos retirées et des sessions auparavant valables. Garde séparément les références des retraits nécessaires à cette reprise et définis une durée de conservation des sauvegardes avec l’hébergeur.

## À compléter avant la mise en ligne

Tout ce qui est surligné en orange pâle est un exemple à remplacer.

### Les coordonnées de l’unité : Réglages → Unité

L’adresse du local, le ou les responsables d’unité (nom, téléphone), l’e-mail du staff d’unité, le logo de l’unité (pour les cartes du staff), le contact de chaque section (e-mail, GSM ou les deux), les liens des groupes WhatsApp, les tarifs de cotisation (un par ligne, par ex. « 80 € pour le 1er enfant »), le responsable et l’e-mail du clan IAmA, le numéro de compte, les places disponibles et les liens des formulaires d’inscription et de contact (Google Forms, extension de formulaire…) s’encodent **une seule fois** dans **Réglages → Unité**. Le staff de chaque section, lui, se gère dans **Staff** (voir plus haut). Ils s’affichent d’eux-mêmes dans le pied de page, l’affiche « Le staff » de chaque page de section (un badge par animateur, le chef en tête, d’après les fiches), les infos pratiques de l’accueil, l’affiche d’inscription, la page Contact et la page Inscription, et le lien « Itinéraire » se calcule à partir de l’adresse. Dans l’éditeur, ces blocs montrent la vraie valeur mais ne se modifient pas sur place : on change la valeur dans les réglages. Un champ vide garde son exemple surligné.

Ces blocs sont liés aux réglages dans les compositions du thème. Une page créée avant cette fonction (ou un bloc recopié à la main) garde son texte figé : recrée-la à partir de sa composition (« Page Contact », « Page Inscription ») pour profiter des réglages.

### Le reste, directement dans les pages

- la phrase d’accroche manuscrite du pied de page (« Toujours prêts, de 6 à 18 ans. ») si tu veux la personnaliser ;
- les photos : une seule photo d’exemple est fournie (celle du dépôt de la fédération).

## Développer

### Essayer en local

Avec [WordPress Playground](https://wordpress.github.io/wordpress-playground/), sans rien installer d’autre que Node, depuis la racine du dépôt :

```sh
mkdir -p /tmp/les-scouts-photos
npx @wp-playground/cli@latest server --port=9400 \
  --mount=.:/wordpress/wp-content/themes/les-scouts-unite \
  --mount=./extension:/wordpress/wp-content/plugins/les-scouts-unite-extension \
  --mount=/tmp/les-scouts-photos:/photos-privees \
  --blueprint=./blueprint.json
```

Le thème est à la racine du dépôt, l’extension dans [`extension/`](extension/). Puis ouvre http://127.0.0.1:9400 (identifiant `admin`, mot de passe `password`). Le contenu d’exemple (pages, articles, réunions) vient de `playground/contenu-demo.php`, partagé avec la démo en ligne (`playground/demo.json`, qui installe le thème depuis GitHub).

Le troisième montage partage les photos privées entre les workers du Playground, hors du dossier public. Le blueprint local ne connecte plus automatiquement tous les visiteurs : connecte-toi avec `admin` pour alimenter le site et ouvre une fenêtre privée pour essayer un lien parent. Le dossier `/tmp/les-scouts-photos` ne doit pas être placé dans le dépôt monté comme thème, qui est public dans WordPress.

### Tester

```sh
tests/lancer.sh            # PHP 8.3, dernière version de WordPress
tests/lancer.sh 7.4 6.6    # les versions minimales annoncées
```

Chaque lancement installe un WordPress neuf (Playground) avec le thème et l’extension, y crée le contenu d’exemple puis lance [`tests/tests.php`](tests/tests.php) : droits des animateurs et du staff (y compris par l’API et XML-RPC), champs des réunions, répétition, annulation, notes protégées, feuilles pour le frigo, QR codes, liens, fiches du staff et cartes, et ce que le thème et l’extension attendent l’un de l’autre. Le résultat s’affiche et reste dans `tests/resultats.txt`.

À chaque envoi sur GitHub, l’action [`verifier`](.github/workflows/verifier.yml) contrôle la syntaxe PHP (7.4 et 8.3) et JavaScript, `theme.json` contre le schéma de WordPress 6.6, les règles de sécurité de WordPress (PHPCS, règles dans [`.phpcs.xml.dist`](.phpcs.xml.dist)) et lance les tests dans les deux configurations.

### Publier une version

1. Monte `Version:` dans `style.css` et dans `extension/les-scouts-unite-extension.php` (la même, par ex. `3.1.0`) et note les changements dans `CHANGELOG.md`, sous un titre `## 3.1.0 – AAAA-MM-JJ`.
2. Commite, puis crée et pousse le tag correspondant :
   ```sh
   git tag v3.1.0 && git push origin main v3.1.0
   ```
3. L’action GitHub [`release`](.github/workflows/release.yml) lance d’abord les vérifications et les tests, vérifie que le tag, `style.css` et l’extension portent la même version et que le CHANGELOG la date, construit `les-scouts-unite.zip` (sans la doc, les fichiers de développement ni l’extension, voir `.gitattributes`) et `les-scouts-unite-extension.zip` (le dossier `extension/`), et publie la version avec les deux zips.

Pour fabriquer les zips à la main (les fichiers ajoutés doivent être commités) :

```sh
git archive --format=zip --prefix=les-scouts-unite/ -o les-scouts-unite.zip HEAD
git archive --format=zip --prefix=les-scouts-unite-extension/ -o les-scouts-unite-extension.zip HEAD:extension
```

### Documentation technique

- [`docs/agenda-module.md`](docs/agenda-module.md) : l’agenda et les droits, de quoi les reproduire ailleurs.
- [`DESIGN.md`](DESIGN.md) : le système graphique (couleurs, typographie, composants).
- [`PRODUCT.md`](PRODUCT.md) : à qui sert le site et ce qu’il doit permettre.

## Polices

La charte recommande Housepaint, mais elle n’a pas de lettres accentuées : les titres sont donc en Muli Black, et Caveat Brush sert pour les annotations manuscrites des affiches.

## Origine et licences

Ce thème est né dans un fork du [template des sites d’unité](https://github.com/lesscouts/template-unite) de Les Scouts asbl, dont il reprend la charte graphique ; il vit désormais dans son propre dépôt. Le code est sous licence MIT (voir [`LICENSE`](LICENSE)). Les logos, toppings et éléments de la charte restent la propriété de Les Scouts asbl. Muli et Caveat Brush sont sous licence SIL Open Font License (texte et mentions dans [`assets/fonts/OFL.txt`](assets/fonts/OFL.txt), joint au zip) ; le thème les sert en WOFF2, limitées aux caractères du français. Les QR codes sont dessinés par [QR Code for PHP](https://github.com/kazuhikoarase/qrcode-generator) de Kazuhiko Arase (licence MIT), copié dans `extension/inc/lib/qrcode.php`.
