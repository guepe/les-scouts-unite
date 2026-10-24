# Changements

Les versions suivent le numéro `Version:` de `style.css`. Chaque version publiée a son zip dans les [Releases](https://github.com/guepe/les-scouts-unite/releases).

## 2.10.0 – non publiée

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
