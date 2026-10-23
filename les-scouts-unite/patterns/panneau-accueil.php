<?php
/**
 * Title: Panneau d'affichage (accueil)
 * Slug: les-scouts-unite/panneau-accueil
 * Categories: scoutsunite, banner
 * Description: Le panneau bleu de l’unité avec les prochaines réunions, l’activité mise en avant et les inscriptions.
 * Keywords: accueil, réunions, agenda, affiche, inscription
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"is-style-panneau","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|70"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","contentSize":"78rem"}} -->
<section class="wp-block-group alignfull is-style-panneau" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:group {"style":{"spacing":{"blockGap":"0.5rem 1.5rem"}},"layout":{"type":"flex","flexWrap":"wrap","verticalAlignment":"bottom"}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":1,"isLink":false,"className":"panneau__titre"} /-->

<!-- wp:paragraph {"className":"panneau__aparte"} -->
<p class="panneau__aparte">ce qui se passe au local</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:columns {"className":"panneau__affiches","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"},"blockGap":{"left":"clamp(1.5rem, 4vw, 3rem)","top":"2.5rem"}}}} -->
<div class="wp-block-columns panneau__affiches" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:column {"width":"57%"} -->
<div class="wp-block-column" style="flex-basis:57%"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:heading {"level":2,"className":"affiche__titre","fontSize":"xx-large"} -->
<h2 class="wp-block-heading affiche__titre has-xx-large-font-size">Prochaines réunions</h2>
<!-- /wp:heading -->

<!-- wp:scoutsunite/prochaines-reunions {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"43%","style":{"spacing":{"blockGap":"2.75rem"}}} -->
<div class="wp-block-column" style="flex-basis:43%"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:scoutsunite/a-la-une /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-affiche-verte","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche-verte"><!-- wp:heading {"level":2,"className":"affiche__titre","fontSize":"x-large"} -->
<h2 class="wp-block-heading affiche__titre has-x-large-font-size">Inscriptions 2026–2027</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Inscris ton enfant dans la section de son âge.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"places"}}}}} -->
<p><mark class="a-completer">Places disponibles : à préciser par le staff.</mark></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/inscription/">S’inscrire</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></section>
<!-- /wp:group -->
