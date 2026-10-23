<?php
/**
 * Title: En-tête de l'unité
 * Slug: les-scouts-unite/entete
 * Categories: header
 * Block Types: core/template-part/header
 * Inserter: no
 */
?>
<!-- wp:group {"tagName":"div","align":"full","className":"barre-federation","style":{"spacing":{"padding":{"top":"0","bottom":"0"}},"elements":{"link":{"color":{"text":"var:preset|color|blanc"}}}},"backgroundColor":"vert-fonce","textColor":"blanc","layout":{"type":"constrained","contentSize":"78rem"}} -->
<div class="wp-block-group alignfull barre-federation has-blanc-color has-vert-fonce-background-color has-text-color has-background has-link-color" style="padding-top:0;padding-bottom:0"><!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"0","bottom":"0"}}}} -->
<p style="margin-top:0;margin-bottom:0"><a href="https://lesscouts.be/" title="Aller sur le site de la fédération Les Scouts">Les Scouts asbl</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"div","align":"full","className":"entete-unite","style":{"spacing":{"padding":{"top":"0.9rem","bottom":"0.9rem"}}},"backgroundColor":"blanc","layout":{"type":"constrained","contentSize":"78rem"}} -->
<div class="wp-block-group alignfull entete-unite has-blanc-background-color has-background" style="padding-top:0.9rem;padding-bottom:0.9rem"><!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"}} -->
<div class="wp-block-group"><!-- wp:group {"style":{"spacing":{"blockGap":"1rem"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
<div class="wp-block-group"><!-- wp:image {"width":"auto","height":"60px","sizeSlug":"full","linkDestination":"custom","className":"entete-unite__logo"} -->
<figure class="wp-block-image size-full is-resized entete-unite__logo"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo scoutsunite_image( 'logos/logo_Federation_couleur_horizontal.png' ); ?>" alt="Les Scouts – accueil de l’unité" style="width:auto;height:60px"/></a></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"entete-unite__nom","layout":{"type":"default"}} -->
<div class="wp-block-group entete-unite__nom"><!-- wp:site-title {"level":0} /-->

<!-- wp:site-tagline {"className":"entete-unite__sous-titre"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"blanc","overlayTextColor":"bleu-fonce","layout":{"type":"flex","justifyContent":"right","orientation":"horizontal"},"style":{"spacing":{"blockGap":"2rem"}}} -->
<!-- wp:navigation-link {"label":"Actualités","url":"/actualites/","kind":"custom"} /-->

<!-- wp:navigation-link {"label":"Agenda","url":"<?php echo esc_url( get_post_type_archive_link( 'reunion' ) ?: home_url( '/agenda/' ) ); ?>","kind":"custom"} /-->

<!-- wp:navigation-submenu {"label":"Sections","url":"/sections/","kind":"custom"} -->
<!-- wp:navigation-link {"label":"Baladins · 6–8 ans","url":"/sections/baladins/","kind":"custom"} /-->

<!-- wp:navigation-link {"label":"Louveteaux · 8–12 ans","url":"/sections/louveteaux/","kind":"custom"} /-->

<!-- wp:navigation-link {"label":"Éclaireurs · 12–16 ans","url":"/sections/eclaireurs/","kind":"custom"} /-->

<!-- wp:navigation-link {"label":"Pionniers · 16–18 ans","url":"/sections/pionniers/","kind":"custom"} /-->

<!-- wp:navigation-link {"label":"IAmA · anciens et amis","url":"/iama/","kind":"custom"} /-->
<!-- /wp:navigation-submenu -->

<!-- wp:navigation-link {"label":"Inscription","url":"/inscription/","kind":"custom"} /-->

<!-- wp:navigation-link {"label":"Contact","url":"/contact/","kind":"custom"} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
