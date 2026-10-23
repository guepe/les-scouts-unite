<?php
/**
 * Title: Dernières actualités
 * Slug: les-scouts-unite/actualites
 * Categories: scoutsunite, query
 * Description: Les quatre derniers articles, le plus récent mis en avant.
 * Keywords: actualités, articles, news, agenda
 */
?>
<!-- wp:group {"tagName":"section","align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"blanc","layout":{"type":"constrained","contentSize":"78rem"}} -->
<section class="wp-block-group alignfull has-blanc-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"bottom"}} -->
<div class="wp-block-group"><!-- wp:heading {"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size">Actualités</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"lien-fleche"} -->
<p class="lien-fleche"><a href="/actualites/">Toutes les actualités</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:query {"queryId":11,"query":{"perPage":4,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"actus","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-query actus" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:post-template -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"3/2","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} /-->

<!-- wp:post-date {"format":"j F Y"} /-->

<!-- wp:post-title {"level":3,"isLink":true,"style":{"spacing":{"margin":{"top":"0.5rem"}}}} /-->

<!-- wp:post-excerpt {"excerptLength":24,"style":{"spacing":{"margin":{"top":"0.5rem"}}}} /-->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:paragraph -->
<p>Pas encore d’actualité. Le staff publie ici les nouvelles de l’unité, les camps et les grands rendez-vous.</p>
<!-- /wp:paragraph -->
<!-- /wp:query-no-results --></div>
<!-- /wp:query --></section>
<!-- /wp:group -->
