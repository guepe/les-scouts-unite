# Changements

Les versions suivent le numéro `Version:` de `style.css`. Chaque version publiée a son zip dans les [Releases](https://github.com/guepe/les-scouts-unite/releases).

## 2.11.0 – non publiée

- **Les camps toujours sur le frigo** : le programme et la convocation à imprimer commencent par un encadré « À retenir » avec les camps à venir de la section et de toute l’unité, même au-delà de la période imprimée (« Camp d’été du mardi 20 juillet au mardi 3 août 2027 »). Un camp annulé n’y figure pas ; la convocation d’un camp ne se rappelle pas elle-même.
- **Une date déjà passée est signalée** dans le panneau « Quand et où ? » : « Cette date est déjà passée : la réunion n’apparaîtra pas sur le site. Vérifie l’année. » (un camp encodé en 2026 au lieu de 2027 n’apparaissait nulle part).
- **Agenda** : des notes coupées après 40 mots se terminent par « Lire la suite », vers la page de la réunion.
- **Un site installé dans un sous-dossier** (exemple.be/scouts/) garde des liens justes : les liens des compositions (« /contact/ », « /sections/… »), recopiés dans les pages, reçoivent le sous-dossier à l’affichage, y compris dans les pages déjà créées. Rien ne change pour un site à la racine.
- **WordPress 6.6 minimum** (le `theme.json` version 3 l’exige ; testé de 6.6 à 7.1, PHP 7.4 à 8.4).
- **Licence des polices jointe au zip** (`assets/fonts/OFL.txt`) et image d’aperçu du thème dans Apparence → Thèmes.
- Accessibilité : un lecteur d’écran n’annonce plus deux fois le nom d’un groupe WhatsApp ; les fiches des temps forts et de l’IAmA sont des titres de niveau 3 sous le titre de leur bloc.
- Le flux iCal et les initiales du staff fonctionnent aussi sans l’extension PHP mbstring.
- **Tests et vérifications automatiques** : 35 tests dans un WordPress neuf (droits, répétition, annulation, notes protégées, frigo, QR codes, liens), lancés avec PHP 7.4 et WordPress 6.6 puis PHP 8.3 et la dernière version, avec la syntaxe PHP et JavaScript, `theme.json` et les règles de sécurité de WordPress, à chaque envoi et avant chaque publication. Les notes de version sur GitHub ont des liens qui fonctionnent.

## 2.10.3 – 2026-10-25

Correctifs de sécurité, après une relecture complète du thème, et des pages plus légères. Les rôles sont réécrits automatiquement à la mise à jour.

- **Une page ne peut plus tomber en boucle** : un bloc « Prochaine réunion » ou « Temps forts » glissé dans les notes d’une réunion affichait cette même réunion à l’infini, et la page de la section, la réunion et la feuille à imprimer tombaient en erreur. Une réunion n’est plus affichée dans ses propres notes.
- **Un animateur ne crée plus de page par aucune voie** : la création était bloquée dans l’éditeur et l’API, mais restait possible par XML-RPC (applications). Sur la page de sa section, il ne change que le contenu : l’état, l’adresse, la page parente, le mot de passe, l’auteur et le modèle restent ceux d’avant.
- **Le Staff d’unité n’a plus le droit au HTML libre** (`unfiltered_html`) : un script glissé dans une page aurait pu s’exécuter chez un administrateur.
- **Une réunion protégée par un mot de passe** garde ses notes pour elle : elles ne sortent plus dans l’agenda, la prochaine réunion, les temps forts, la une, le calendrier du téléphone ni les feuilles à imprimer.
- **Dates et heures vérifiées par le serveur** : une date invalide envoyée par l’API ne fait plus tomber le calendrier des parents ; les réunions déjà enregistrées sont relues avec les mêmes contrôles.
- **Répétition chaque semaine** : prolonger la date de fin ne crée plus que les semaines qui manquent (plus de doublons) ; la raccourcir ne fait rien ; au plus 60 semaines après la réunion d’origine. Une copie ratée reprend au prochain enregistrement.
- Un animateur ne lit plus les réunions et documents privés des autres sections, et le fichier d’un document doit être un fichier de la médiathèque qu’il peut voir.
- Un lien WhatsApp trop long pour un QR code (au-delà d’environ 210 caractères) ne fait plus tomber les pages : le lien s’affiche sans QR code, et Réglages → Unité le signale.
- **Pages plus légères** : une première visite télécharge environ 1 Mo de moins. Les polices passent en WOFF2, limitées aux caractères du français (743 Ko → 200 Ko) ; les deux principales sont préchargées. Les logos et la photo sont redimensionnés à leur taille d’affichage et recompressés, sans différence visible (565 Ko → 125 Ko). Les noms des fichiers ne changent pas : les pages déjà créées en profitent aussi.

## 2.10.2 – 2026-10-24

- Éditeur de réunion : le panneau « Quand et où ? » s’ouvre aussi sur une réunion déjà enregistrée. Les préférences de WordPress, chargées juste après l’ouverture, le refermaient. On peut toujours le replier soi-même ensuite.
- Modes d’emploi illustrés : [le site en 8 fiches pour le staff d’unité](docs/fiches-chefs-unite/), à côté des [7 fiches des animateurs](docs/fiches-animateurs/), en images et en PDF.

## 2.10.1 – 2026-10-24

- **Correction importante des droits des animateurs** : sur sa page de section, un animateur voyait « Soumettre à la relecture » au lieu d’« Enregistrer ». S’il cliquait, la page passait en attente de relecture et disparaissait du site. L’animateur peut désormais enregistrer la page de ses sections directement, et une page publiée qu’il modifie reste publiée. Il ne peut toujours pas créer de page, ni toucher à celles des autres sections. Les rôles sont réécrits automatiquement à la mise à jour.
- Modes d’emploi : « Enregistrer » à la place de « Mettre à jour », le libellé de WordPress 7.

## 2.10.0 – 2026-10-24

- **Documents à télécharger** : nouveau menu **Documents** dans l’administration (titre, fichier de la médiathèque, section ou toute l’unité, une ligne d’explication, ordre). Ils s’affichent sous « Les documents » sur chaque page de section (avec ceux de toute l’unité) et sur une nouvelle page « Documents », rangés par section. Un animateur ne gère que les documents de ses sections. Les pages de section déjà créées reçoivent le bloc automatiquement.
- **Questions fréquentes** : composition « Page Questions fréquentes », avec une question repliée par bloc Détails, rangée par thème (inscription, vie de la section, week-ends et camp, argent, santé et sécurité). Les questions sont des exemples à compléter.
- Calendrier du téléphone : si le site est resté sur le fuseau UTC, les heures sont lues comme belges (Bruxelles) ; un avis invite l’administrateur à choisir le fuseau. Les apostrophes des titres ne sortent plus en code HTML (« d&rsquo;automne »). Une fin à minuit tombe le lendemain.
- « 0 h » s’écrit « minuit » (« 20 h – minuit »). Une réunion intitulée du nom de sa section (« Baladins ») est traitée comme « Réunion » : plus de « Baladins · Baladins ».
- Contact d’une section : un champ **GSM de la section** à côté de son e-mail, dans Réglages → Unité. On remplit l’un, l’autre ou les deux ; la page Contact, l’affiche du staff de la page de section (« Pour joindre la section ») et les feuilles à imprimer montrent ce qui est rempli.
- Menu : un sous-menu **Parents** (Documents, Questions fréquentes) dans l’en-tête, et les mêmes liens dans le pied de page. Chaque lien n’apparaît que si sa page existe et est publiée.

## 2.9.0 – 2026-10-24

- **L’agenda dans le calendrier du téléphone** : « Ajouter à mon calendrier » abonne Google Agenda, Apple Calendrier ou Outlook à l’agenda d’une section (avec les activités de toute l’unité) ou de toute l’unité. Une réunion ajoutée, déplacée ou supprimée se met à jour d’elle-même. Sur la page d’une réunion, le même lien ajoute cette seule réunion.
- **Réunion annulée** : une case « Réunion annulée » et un mot d’explication facultatif dans le panneau « Quand et où ? ». La réunion reste affichée jusqu’à sa date, barrée, avec une étiquette rouge « Annulée » : accueil, page de section (la prochaine réunion passe à la suivante, avec un rappel au-dessus), agenda, page de la réunion, À la une, temps forts, feuilles à imprimer et calendrier du téléphone.
- **Bon à savoir** : par section, dans Réglages → Unité, ce qui ne change pas d’une semaine à l’autre (horaire habituel, lieu, uniforme, à emporter). Les infos s’affichent sous la prochaine réunion de la page de section et sur les feuilles à imprimer.
- Le programme à imprimer passe sur deux colonnes au-delà de 8 réunions, pour que trois mois de réunions tiennent sur une feuille.
- Les liens d’impression deviennent « Le programme à mettre sur le frigo » et « La convocation à mettre sur le frigo », avec une icône de frigo. Ils s’affichent aussi quand aucune réunion n’est encore prévue.

## 2.8.0 – 2026-10-24

- **Feuilles à imprimer** pour le frigo, à imprimer ou à enregistrer en PDF : le programme d’une section (ou de toute l’unité) sur 1 à 12 mois, et la convocation d’une réunion avec ses notes. Chaque feuille porte le logo de la section, le staff et les contacts de Réglages → Unité. Les liens se trouvent sur les pages de section, sous l’agenda, sur la page d’une réunion et dans la liste des réunions de l’administration.
- **Fonction et GSM des animateurs** dans Réglages → Unité : « Castor – Marie (santé, communication) 0470 12 34 56 ». Ils s’affichent sous chaque badge du staff (GSM cliquable) et sur les feuilles à imprimer. Les anciennes lignes « Totem – Prénom » restent valables.
- **Groupes WhatsApp** : un lien d’invitation par section, pour toute l’unité et pour le clan IAmA, dans Réglages → Unité. Chaque groupe s’affiche avec son QR code, dessiné par le thème sans service extérieur : dans l’affiche du staff de la section, sur la page Contact (nouveau bloc « Groupes WhatsApp ») et sur les feuilles à imprimer.

## 2.7.1 – 2026-10-23

- Un week-end ou un camp répété chaque semaine garde sa date de fin dans chaque copie.
- Un animateur ne peut plus modifier ni supprimer une réunion sans section créée par quelqu’un d’autre.
- Documentation du thème (PRODUCT.md, DESIGN.md) et adresse du thème mises à jour pour le dépôt `les-scouts-unite`.

## 2.7.0 – 2026-10-23

- Rôles **Animateur** (rattaché à ses sections dans son profil) et **Staff d’unité** (tout le contenu et Réglages → Unité, sans l’administration technique). Un animateur ne gère que les réunions et la page de ses sections, en « textes et images seulement » ; les limites sont vérifiées par WordPress.
- Type de réunion **Réunion spéciale** ; le bloc du bas des pages de section devient **Les temps forts**.
- Pied de page : colonnes rééquilibrées, adresses e-mail coupées après l’arobase.
- Le thème a maintenant son propre dépôt ; les captures d’écran de test livrées par erreur dans le zip sont retirées.

## 2.6.3 – 2026-10-23

- **IAmA, les anciens et amis** : page dédiée avec le logo officiel, responsable et e-mail du clan dans Réglages → Unité, section « IAmA » dans l’agenda et bloc « Activités IAmA », entrée dans le menu Sections et le pied de page, mot sous la règle des âges.

## 2.5.0 – 2026-10-23

- Types **Week-end** et **Camp** avec date de fin ; bloc sur les pages de section ; étiquettes dans l’agenda et sur l’accueil.
- « À la une » : le titre suffit, plus de lien « Détails » (2.4.1).

## 2.4.0 – 2026-10-23

- Staff de chaque section (chef puis animateurs, « Totem – Prénom ») dans Réglages → Unité, affiché en badges sur la page de la section.

## 2.3.0 – 2026-10-23

- **Réglages → Unité** : coordonnées saisies une fois et affichées partout (liaisons de blocs), tarifs de cotisation, liens des formulaires d’inscription et de contact.
- Case « Mettre en avant sur l’accueil » et affiche **À la une**.
- Favicon aux couleurs de la fédération.
- Reprise automatique des réunions, blocs et images d’un site installé avec l’ancien thème `scouts-11om-magnee`.

## 2.0.0 – 2026-10-23

- Thème générique pour toute unité, renommé `les-scouts-unite` : le nom de l’unité vient du titre et du slogan du site, les données locales sont des exemples à compléter.

## 1.x

- Thème `scouts-11om-magnee`, écrit pour l’unité 11OM Magnée.
