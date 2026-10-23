<?php
/**
 * Title: Infos pratiques
 * Slug: les-scouts-unite/infos-pratiques
 * Categories: scoutsunite
 * Description: Le local, le staff d'unité et une photo, épinglés sur le panneau.
 * Keywords: contact, local, adresse, staff, infos
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"is-style-panneau","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","contentSize":"78rem"}} -->
<section class="wp-block-group alignfull is-style-panneau" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:group {"style":{"spacing":{"blockGap":"0.5rem 1.5rem"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"bottom"}} -->
<div class="wp-block-group"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size">Infos pratiques</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"panneau__aparte"} -->
<p class="panneau__aparte">pour les parents</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:columns {"className":"affiches","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"},"blockGap":{"left":"clamp(1.5rem, 4vw, 3rem)","top":"2.75rem"}}}} -->
<div class="wp-block-columns affiches" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:group {"className":"is-style-affiche","style":{"spacing":{"padding":{"top":"0.85rem","right":"0.85rem","bottom":"1.25rem","left":"0.85rem"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche" style="padding-top:0.85rem;padding-right:0.85rem;padding-bottom:1.25rem;padding-left:0.85rem"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","aspectRatio":"2/1","scale":"cover"} -->
<figure class="wp-block-image size-full"><img src="<?php echo scoutsunite_image( 'photos/coucher-soleil.png' ); ?>" alt="Une tour de pionniers en bois se découpe sur le ciel orange d’un coucher de soleil au camp" style="aspect-ratio:2/1;object-fit:cover"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"is-style-manuscrit","style":{"spacing":{"margin":{"top":"0.85rem"}}}} -->
<p class="is-style-manuscrit" style="margin-top:0.85rem">chaque été, chaque section part en camp</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:heading {"level":3,"className":"affiche__titre","fontSize":"x-large","metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"adresse"}}}}} -->
<h3 class="wp-block-heading affiche__titre has-x-large-font-size"><mark class="a-completer">Rue du Local 1</mark><br><mark class="a-completer">0000 Localité</mark></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Les réunions ont lieu le samedi après-midi, sauf mention contraire sur la page de la section.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"lien-fleche","metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"itineraire"}}}}} -->
<p class="lien-fleche"><a href="https://www.openstreetmap.org/">Itinéraire</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:heading {"level":3,"className":"affiche__titre","fontSize":"x-large"} -->
<h3 class="wp-block-heading affiche__titre has-x-large-font-size">Le staff d’unité</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"responsable"}}}}} -->
<p><strong><mark class="a-completer">Prénom Nom</mark></strong>, responsable d’unité<br><mark class="a-completer">0400 00 00 00</mark></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/contact/">Nous écrire</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->
