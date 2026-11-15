# Le site de l’unité en 8 fiches – pour les animateurs

Un geste par fiche, avec les vrais écrans de WordPress. Les fiches se lisent sur un téléphone et s’envoient en images dans le groupe WhatsApp du staff.

**[Télécharger toutes les fiches en PDF](fiches-animateurs.pdf)** (9 pages)

Le même contenu en texte, plus détaillé : [Mode d’emploi pour les animateurs](../mode-emploi-animateurs.md). Pour le staff d’unité : [les 9 fiches des chefs d’unité](../fiches-chefs-unite/) et le [mode d’emploi pour les chefs d’unité](../mode-emploi-chefs-unite.md).

<p>
<a href="00-couverture.png"><img src="00-couverture.png" width="260" alt="Couverture : le site de l’unité en 8 fiches"></a>
<a href="01-ajouter-une-reunion.png"><img src="01-ajouter-une-reunion.png" width="260" alt="Fiche 1 : ajouter une réunion"></a>
<a href="02-annuler-une-reunion.png"><img src="02-annuler-une-reunion.png" width="260" alt="Fiche 2 : une réunion n’a pas lieu ?"></a>
<a href="03-toutes-les-semaines.png"><img src="03-toutes-les-semaines.png" width="260" alt="Fiche 3 : tous les samedis, en une fois"></a>
<a href="04-ajouter-un-document.png"><img src="04-ajouter-un-document.png" width="260" alt="Fiche 4 : ajouter un document"></a>
<a href="05-ta-page-de-section.png"><img src="05-ta-page-de-section.png" width="260" alt="Fiche 5 : changer le texte de ta page"></a>
<a href="06-ce-que-voient-les-parents.png"><img src="06-ce-que-voient-les-parents.png" width="260" alt="Fiche 6 : ce que voient les parents"></a>
<a href="07-ca-coince.png"><img src="07-ca-coince.png" width="260" alt="Fiche 7 : ça coince ?"></a>
<a href="08-photos-camp.png"><img src="08-photos-camp.png" width="260" alt="Fiche 8 : Les photos du camp, depuis ton téléphone"></a>
</p>

| Fiche | Ce qu’on y apprend |
|---|---|
| [0 · Couverture](00-couverture.png) | Se connecter, et le menu de l’animateur (Agenda, Documents, Pages) |
| [1 · Ajouter une réunion](01-ajouter-une-reunion.png) | Le panneau « Quand et où ? », puis Publier deux fois |
| [2 · Une réunion n’a pas lieu ?](02-annuler-une-reunion.png) | Annuler plutôt que supprimer, avec un mot d’explication |
| [3 · Tous les samedis, en une fois](03-toutes-les-semaines.png) | Répéter une réunion chaque semaine, retirer les congés |
| [4 · Ajouter un document](04-ajouter-un-document.png) | Téléverser un PDF pour les parents |
| [5 · Changer le texte de ta page](05-ta-page-de-section.png) | Réécrire la page de sa section, et qui change quoi |
| [6 · Ce que voient les parents](06-ce-que-voient-les-parents.png) | La page de section, le programme frigo, le calendrier |
| [7 · Ça coince ?](07-ca-coince.png) | Les six soucis les plus courants |
| [8 · Les photos du camp, depuis ton téléphone](08-photos-camp.png) | Créer plusieurs albums, publier, partager le lien et le QR, supprimer une photo |

Fiches de la version **3.5.0** (15 novembre 2026), avec des captures prises sur le site de démonstration.

## Mettre les fiches à jour

### Fiche 8 : les photos de camp

[La fiche illustrée « Les photos du camp, depuis ton téléphone »](08-photos-camp.png) explique l’envoi depuis la pellicule, la publication directe et le partage autonome. [Source modifiable](source/Photos.dc.html) · [Écran parent de démonstration](source/captures/photos-parent.png).

Cette fiche est incluse dans le PDF de neuf pages ci-dessus.

Les sources sont dans [`source/`](source/) : une page `.dc.html` par fiche (format du canevas Claude Design), `canvas.json` et les captures d’écran dans `source/captures/`. Après un changement d’écran dans WordPress ou dans le thème, refaire la capture concernée, adapter la fiche, puis regénérer les images et le PDF.

Le script [`../generer-fiches.cjs`](../generer-fiches.cjs) régénère les images et les deux PDF depuis ces sources, sans ressource distante. Il demande Playwright avec Chromium et Python avec `pypdf`. Lance-le depuis la racine : `node docs/generer-fiches.cjs`. Les variables `SCOUTSUNITE_PLAYWRIGHT` et `SCOUTSUNITE_PYTHON` permettent d’utiliser des installations existantes.
