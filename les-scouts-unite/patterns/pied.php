<?php
/**
 * Title: Pied de page de l'unité
 * Slug: les-scouts-unite/pied
 * Categories: footer
 * Block Types: core/template-part/footer
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"footer","align":"full","className":"is-style-panneau pied-unite","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|50"},"margin":{"top":"0"}},"elements":{"link":{"color":{"text":"var:preset|color|blanc"}}}},"textColor":"blanc","layout":{"type":"constrained","contentSize":"78rem"}} -->
<footer class="wp-block-group alignfull is-style-panneau pied-unite has-blanc-color has-text-color has-link-color" style="margin-top:0;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:site-title {"level":2,"isLink":false,"style":{"typography":{"fontSize":"clamp(2.25rem, 5vw, 3.5rem)","lineHeight":"1.05","letterSpacing":"-0.02em"}}} /-->

<!-- wp:paragraph {"className":"is-style-manuscrit"} -->
<p class="is-style-manuscrit">Toujours prêts, de 6 à 18 ans.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1rem"}}} -->
<h3 class="wp-block-heading" style="font-size:1rem">Le site</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"is-style-default","style":{"spacing":{"padding":{"left":"0"}},"typography":{"lineHeight":"2"}}} -->
<ul style="padding-left:0;line-height:2;list-style:none" class="wp-block-list is-style-default"><!-- wp:list-item -->
<li><a href="/actualites/">Actualités</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="<?php echo esc_url( get_post_type_archive_link( 'reunion' ) ?: home_url( '/agenda/' ) ); ?>">Agenda</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/sections/">Les sections</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/inscription/">Inscription</a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/contact/">Contact</a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1rem"}}} -->
<h3 class="wp-block-heading" style="font-size:1rem">Le local</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"adresse"}}}}} -->
<p><mark class="a-completer">Rue du Local 1</mark><br><mark class="a-completer">0000 Localité</mark></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"scoutsunite/unite","args":{"key":"email-staff"}}}}} -->
<p><mark class="a-completer">staff@example.org</mark></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"full","linkDestination":"custom","className":"pied-unite__logo"} -->
<figure class="wp-block-image size-full pied-unite__logo"><a href="https://lesscouts.be/"><img src="<?php echo scoutsunite_image( 'logos/logo_Federation_blanc.png' ); ?>" alt="Les Scouts asbl"/></a></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"pied-unite__petit","fontSize":"small"} -->
<p class="pied-unite__petit has-small-font-size">Notre unité est membre de <a href="https://lesscouts.be/">Les Scouts asbl</a>, Fédération des Scouts Baden-Powell de Belgique.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></footer>
<!-- /wp:group -->
