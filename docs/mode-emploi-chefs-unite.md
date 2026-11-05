# Mode d'emploi du site – Chefs d'unité

Thème 2.10.0 · 24 octobre 2026

> Plus rapide à lire : [le site en 8 fiches](fiches-chefs-unite/), avec les vrais écrans ([PDF](fiches-chefs-unite/fiches-chefs-unite.pdf)).

Ce guide est pour le staff d’unité : régler le site une fois, donner accès aux animateurs, puis tenir à jour ce qui concerne toute l’unité. Pour les réunions et les documents d’une section, le [guide des animateurs](mode-emploi-animateurs.md) explique le détail ; tu peux faire tout ce qu’il décrit, pour toutes les sections.

## Les rôles

Le site a trois rôles utiles. Chacun ne voit que ce qu’il peut faire, et WordPress vérifie lui-même les limites.

|  | Administrateur | Staff d’unité | Animateur |
| --- | --- | --- | --- |
| Réunions et documents | toutes les sections et toute l’unité | toutes les sections et toute l’unité | ses sections seulement |
| Pages | toutes | toutes | textes et images de la page de ses sections |
| Actualités (articles) | oui | oui | non |
| Réglages → Unité | oui | oui | non |
| Comptes, sections d’un animateur | oui | non | non |
| Réglages généraux, thème, extensions | oui | non | non |

Dans la pratique : une ou deux personnes de confiance sont **Administrateur**, le staff d’unité a le rôle **Staff d’unité**, et chaque animateur a le rôle **Animateur**.

## Donner accès à un animateur

Cette étape demande un compte **Administrateur** : le rôle Staff d’unité ne crée pas de compte.

1. **Comptes → Ajouter** : identifiant, adresse e-mail, et le **Rôle** « Animateur ». Coche « Envoyer une notification » : la personne reçoit un lien pour choisir son mot de passe.
2. Clique sur **Ajouter un compte**.
3. Ouvre le compte (**Comptes → Tous les comptes**, clique sur son nom). En bas, dans **Sections de l’animateur**, coche sa section (ou ses sections), puis **Mettre à jour le profil**.
4. Envoie-lui le guide des animateurs.

Un animateur qui change de section : décoche l’ancienne, coche la nouvelle. Un animateur qui quitte le staff : change son rôle en « Aucun rôle pour ce site », ou supprime le compte en attribuant ses contenus à quelqu’un d’autre.

## Réglages → Unité

Toutes les informations de l’unité s’encodent une seule fois dans **Réglages → Unité**, puis s’affichent d’elles-mêmes partout où il le faut. Un champ laissé vide garde un exemple surligné en orange sur le site : on voit tout de suite ce qu’il reste à compléter. Clique sur **Enregistrer les coordonnées** en bas de la page.

| Groupe | Ce qu’on y met | Où ça s’affiche |
| --- | --- | --- |
| Le local | Rue et numéro, code postal et localité | Pied de page, Contact, lien Itinéraire, adresse dans le calendrier des parents |
| Le staff d’unité | Responsable(s), téléphone, e-mail | Contact, infos pratiques de l’accueil, feuilles à imprimer |
| L’unité | Le logo de l’unité | Cartes du staff |
| Joindre les sections | Par section : l’e-mail et/ou le GSM de la section | Contact, affiche « Le staff » de chaque page de section, feuilles à imprimer |
| Les groupes WhatsApp | Le lien d’invitation de chaque groupe (unité, sections, IAmA) | Pages de section, page Contact, feuilles à imprimer, avec un QR code |
| Bon à savoir | Par section : ce qui ne change pas d’une semaine à l’autre | Sous la prochaine réunion de chaque page de section, feuilles à imprimer |
| Le clan IAmA | Responsable et e-mail | Page IAmA, Contact |
| Les formulaires | Liens des formulaires d’inscription et de contact | Boutons des pages Inscription et Contact |
| Les inscriptions | Tarifs de cotisation (un par ligne), numéro de compte, places disponibles | Page Inscription, affiche de l’accueil |

**Le staff** ne se met plus dans Réglages → Unité : chaque personne a sa fiche dans **Staff** (voir plus bas).

**Le Bon à savoir**, une info par ligne. Un mot suivi de deux-points passe en gras :

```
Réunions : le samedi de 14 h à 17 h, au local
Uniforme : pull de la section, foulard
À emporter : une gourde et un goûter
```

**Les groupes WhatsApp** : dans WhatsApp, ouvre les infos du groupe, puis **Inviter via un lien**, et copie ce lien ici. Toute personne qui a le lien peut demander à rejoindre le groupe : garde l’approbation des nouveaux membres activée.

## L’agenda et les documents de l’unité

Les activités communes s’encodent comme une réunion de section (voir le guide des animateurs), avec la section **Toute l’unité**. Elles s’affichent alors dans l’agenda, sur chaque page de section (dans « Les temps forts » pour un week-end, un camp ou une réunion spéciale), sur le programme à imprimer et dans le calendrier de tous les parents. La section **IAmA** sert aux activités du clan des anciens.

**L’affiche À la une**, en haut de l’accueil, montre la prochaine activité cochée **Mettre en avant sur l’accueil**, puis les suivantes. Une fois sa date passée, la suivante prend sa place ; sans activité cochée, c’est la dernière actualité. Garde cette case pour les grands événements : week-end d’unité, souper, fête, camp.

**Les documents de toute l’unité** (fiche médicale, autorisation parentale, droit à l’image, règlement) s’ajoutent dans **Documents → Ajouter**, avec « Pour qui ? » = **Toute l’unité**. Ils s’affichent sur chaque page de section et en tête de la page Documents. Seuls le staff d’unité et les administrateurs peuvent les gérer.

**Une réunion mal placée** par un animateur se corrige depuis **Agenda → Toutes les réunions** : tu vois et modifies les réunions de toutes les sections.

## Les pages du site

Le menu et les liens du site pointent vers des adresses fixes. Chaque page doit donc avoir exactement l’identifiant ci-dessous. L’identifiant se voit et se change dans **Pages → Toutes les pages → Modification rapide**. À la création d’une page, WordPress propose les compositions de la catégorie **Pages de l’unité** : choisis celle indiquée, puis remplace les exemples surlignés.

| Page | Identifiant | Composition |
| --- | --- | --- |
| Actualités | `actualites` | vide (puis Réglages → Lecture : page des articles) |
| Les sections | `sections` | Page Les sections |
| Baladins, Louveteaux, Éclaireurs, Pionniers | `baladins`, `louveteaux`, `eclaireurs`, `pionniers` (page parente : Les sections) | Page de section, avec le modèle Page pleine largeur (sans titre) |
| Inscription | `inscription` | Page Inscription |
| Contact | `contact` | Page Contact |
| IAmA – Anciens et amis | `iama` | Page IAmA |
| Documents | `documents` | Page Documents |
| Questions fréquentes | `questions-frequentes` | Page Questions fréquentes |

**Le menu** se construit tout seul. Les pages Documents et Questions fréquentes apparaissent dans le sous-menu **Parents** et dans le pied de page dès qu’elles sont publiées. Une page en brouillon ou avec un autre identifiant n’y apparaît pas.

**Les questions fréquentes** : chaque question est un bloc **Détails**, avec la question en titre et la réponse dedans. Pour en ajouter une, duplique un bloc existant (les trois points de la barre du bloc → **Dupliquer**). Supprime les exemples que tu n’utilises pas.

**Les actualités** sont des articles : **Articles → Ajouter**. La dernière s’affiche aussi sur l’accueil quand aucune activité n’est mise en avant.

## Réglages à vérifier une fois, et mise à jour du thème

Ces réglages demandent un compte **Administrateur**.

- [ ] **Réglages → Général → Fuseau horaire** : **Bruxelles**. Sinon, les heures et le passage d’une réunion dans le passé sont décalés. Un avis le rappelle dans l’administration tant que le site est en UTC.
- [ ] **Réglages → Général** : le **titre du site** est le nom de l’unité (par exemple « Unité 12XY Localité ») et le **slogan** sa ligne de présentation. Ils s’affichent dans l’en-tête, sur l’accueil, le pied de page et les feuilles à imprimer.
- [ ] **Réglages → Permaliens** : « Titre de la publication », puis **Enregistrer** (sans quoi l’adresse /agenda/ ne fonctionne pas).
- [ ] **Réglages → Lecture** : la page des articles = Actualités.

**Mettre à jour le site** quand une nouvelle version sort. Depuis la 3.0, il y a deux zips, toujours au même numéro : l’extension (l’agenda, les documents, les droits, Réglages → Unité) et le thème (l’apparence). Il faut les deux.

1. Télécharge `les-scouts-unite-extension.zip` et `les-scouts-unite.zip` depuis la [dernière version publiée](https://github.com/guepe/les-scouts-unite/releases/latest).
2. D’abord l’extension : **Extensions → Ajouter → Téléverser une extension**, choisis `les-scouts-unite-extension.zip`, puis **Remplacer l’actuelle par la version téléversée** (la première fois : **Activer**).
3. Puis le thème : **Apparence → Thèmes → Ajouter → Téléverser un thème**, choisis `les-scouts-unite.zip`, puis **Remplacer l’actuel par la version téléversée**.
4. Ouvre une page de section et l’agenda, et vérifie que tout s’affiche. Les réunions, documents, pages et Réglages → Unité ne bougent pas.

Si un message en haut de l’administration dit que l’extension manque ou attend le thème 3.0, fais l’étape qui manque.

Si le site montre encore l’ancienne version après quelques minutes, c’est le cache PHP de l’hébergeur : vide-le dans son panneau (« Vider OPcache » ou « Redémarrer PHP »). Évite de modifier l’en-tête ou le pied de page dans l’éditeur de site : une version modifiée ne suit plus les mises à jour du thème (le nouveau menu, par exemple). Pour revenir à celle du thème : **Apparence → Éditeur → Compositions → En-tête → Réinitialiser**.

## Le staff et les cartes

Chaque membre du staff a une fiche dans **Staff → Ajouter** : les animateurs des sections, le staff d’unité et le clan IAmA. Le prénom suffit pour commencer ; le reste se complète plus tard.

| Champ | Où il s’affiche |
| --- | --- |
| Totem, prénom, fonction, GSM | Affiche « Le staff » de la page de section, page Contact, feuilles pour le frigo, carte |
| Nom, quali, photo | Sur la carte seulement, jamais sur le site |
| Totem de meute (Louveteaux) | Remplace le totem partout : site, frigo et carte |
| Section, chef ou cheffe, il ou elle | Le rôle écrit sur la carte : « Cheffe de meute », « Chef de troupe », « Animatrice »… |
| Ordre | L’ordre sur la page de section, après le chef ou la cheffe |
| Compte du site | La personne peut modifier elle-même sa fiche (pas sa section ni son rôle de chef) |
| Ancien | La fiche est gardée, mais disparaît du site et des cartes de la section |

**Imprimer les cartes** : en haut de **Staff**, choisis « tout le staff » ou une section ; sous une fiche, « Imprimer la carte ». Imprime en **recto-verso, retourné sur le bord long**, à **100 %** (sans « Ajuster à la page »), puis découpe le long des traits. Chaque feuille de 10 rectos est suivie de ses versos. Fais un essai sur une feuille ordinaire avant d’imprimer sur du papier épais. Au verso, le QR code mène au groupe WhatsApp de la section ; sur les cartes du staff d’unité, il mène au formulaire d’inscription (son lien se met dans Réglages → Unité).

La photo ne va pas dans la médiathèque : elle reste privée, visible seulement des personnes connectées qui ont accès à la fiche. Choisis une photo en hauteur, le visage bien au centre.

Quand quelqu’un quitte le staff, coche **Ancien** plutôt que de supprimer la fiche. Quand quelqu’un change de section, change sa section sur la fiche et imprime-lui une nouvelle carte.

## En cas de souci

| Tu constates | Que faire |
| --- | --- |
| Un texte surligné en orange sur le site | Un champ de Réglages → Unité est vide : remplis-le |
| Une page n’apparaît pas dans le menu Parents | Vérifie qu’elle est publiée et que son identifiant est exactement `documents` ou `questions-frequentes` |
| L’adresse /agenda/ donne « Page non trouvée » | Réglages → Permaliens → Enregistrer, sans rien changer |
| Les heures sont décalées d’une ou deux heures | Fuseau horaire sur Bruxelles (Réglages → Général) |
| Un animateur ne voit pas ses réunions, ou ne peut pas publier | Dans son profil, coche ses sections ; vérifie que son rôle est Animateur |
| Un animateur a besoin de modifier une autre section | Coche aussi cette section dans son profil |
| Un document ne s’affiche pas | Il lui faut un fichier et le statut Publié ; un avis le signale dans l’administration |
| Le site montre l’ancienne version après une mise à jour | Recharge avec Cmd + Maj + R (Ctrl + F5 sur PC), puis vide le cache PHP chez l’hébergeur |
| Juste après la mise à jour vers la 3.x, une erreur « Failed opening required … inc/agenda.php » | Le cache PHP de l’hébergeur garde l’ancien thème : vide-le (« Vider OPcache » ou « Redémarrer PHP »). Voir le README |
| Le verso d’une carte ne tombe pas derrière son recto | Imprime en recto-verso « bord long » et à 100 % ; fais d’abord un essai sur papier ordinaire |
| Le calendrier d’un parent ne suit pas un changement | C’est normal pendant quelques heures : Google Agenda relit l’abonnement toutes les 12 à 24 heures |

Pour aller plus loin, le [README du thème](../README.md) décrit chaque fonction en détail.
