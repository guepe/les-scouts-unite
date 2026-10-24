<?php
/**
 * Title: Page « Contact »
 * Slug: les-scouts-unite/page-contact
 * Categories: scoutsunite-pages
 * Keywords: contact, staff, adresse
 * Block Types: core/post-content
 * Post Types: page
 * Viewport Width: 1400
 */
?>
<!-- wp:paragraph {"fontSize":"large"} -->
<p class="has-large-font-size">Pour une question sur une réunion, écris directement au staff de la section. Pour le reste (inscriptions, cotisation, location du local), c’est le staff d’unité.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"affiches","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"},"blockGap":{"left":"var:preset|spacing|50","top":"2.75rem"}}}} -->
<div class="wp-block-columns affiches" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:heading {"level":2,"className":"affiche__titre","fontSize":"large"} -->
<h2 class="wp-block-heading affiche__titre has-large-font-size">Le staff d’unité</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"staff-unite"}}}}} -->
<p><strong><mark class="a-completer">Prénom Nom</mark></strong><br><mark class="a-completer">0400 00 00 00</mark><br><mark class="a-completer">staff@example.org</mark></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:heading {"level":2,"className":"affiche__titre","fontSize":"large"} -->
<h2 class="wp-block-heading affiche__titre has-large-font-size">Le local</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"adresse"}}}}} -->
<p><mark class="a-completer">Rue du Local 1</mark><br><mark class="a-completer">0000 Localité</mark></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"lien-fleche","metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"itineraire"}}}}} -->
<p class="lien-fleche"><a href="https://www.openstreetmap.org/">Itinéraire</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--70)">Les staffs de section</h2>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:group {"className":"is-style-ligne-baladins","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-ligne-baladins"><!-- wp:paragraph -->
<p>Baladins</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"baladins-chef"}}}}} -->
<p><mark class="a-completer">Prénom Nom</mark></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"baladins-email"}}}}} -->
<p><mark class="a-completer">baladins@example.org</mark></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-ligne-louveteaux","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-ligne-louveteaux"><!-- wp:paragraph -->
<p>Louveteaux</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"louveteaux-chef"}}}}} -->
<p><mark class="a-completer">Prénom Nom</mark></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"louveteaux-email"}}}}} -->
<p><mark class="a-completer">louveteaux@example.org</mark></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-ligne-eclaireurs","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-ligne-eclaireurs"><!-- wp:paragraph -->
<p>Éclaireurs</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"eclaireurs-chef"}}}}} -->
<p><mark class="a-completer">Prénom Nom</mark></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"eclaireurs-email"}}}}} -->
<p><mark class="a-completer">eclaireurs@example.org</mark></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-ligne-pionniers","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-ligne-pionniers"><!-- wp:paragraph -->
<p>Pionniers</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"pionniers-chef"}}}}} -->
<p><mark class="a-completer">Prénom Nom</mark></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"pionniers-email"}}}}} -->
<p><mark class="a-completer">pionniers@example.org</mark></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-ligne-iama","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-ligne-iama"><!-- wp:paragraph -->
<p><a href="/iama/">IAmA</a></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"iama-chef"}}}}} -->
<p><mark class="a-completer">Prénom Nom</mark></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"iama-email"}}}}} -->
<p><mark class="a-completer">iama@example.org</mark></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:heading {"style":{"spacing":{"margin":{"top":"var:preset|spacing|70"}}}} -->
<h2 class="wp-block-heading" style="margin-top:var(--wp--preset--spacing--70)">Les groupes WhatsApp</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Les infos de dernière minute passent par WhatsApp : scanne le code de ta section avec ton téléphone, ou clique dessus.</p>
<!-- /wp:paragraph -->

<!-- wp:scoutsunite/whatsapp /-->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"formulaire-contact"}}}},"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
<p style="margin-top:var(--wp--preset--spacing--60)"><mark class="a-completer">Ajoute le lien du formulaire de contact dans Réglages → Unité.</mark></p>
<!-- /wp:paragraph -->
