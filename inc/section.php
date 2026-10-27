<?php
/**
 * Composition « page de section », partagée par les quatre sections.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Données de chaque section, d'après la pédagogie Les Scouts.
 *
 * @return array
 */
function scoutsunite_sections() {
	return array(
		'baladins'   => array(
			'nom'      => 'Baladins',
			'ages'     => '6 – 8 ans',
			'logo'     => 'logo_Baladins.png',
			'groupe'   => 'la Ronde',
			'staff'    => 'de la Ronde',
			'accroche' => 'Le jeu, l’imaginaire et la vie en Ronde : on découvre le groupe, la nature et les premières nuits loin de la maison.',
		),
		'louveteaux' => array(
			'nom'      => 'Louveteaux',
			'ages'     => '8 – 12 ans',
			'logo'     => 'logo_Louveteaux.png',
			'groupe'   => 'la Meute',
			'staff'    => 'de la Meute',
			'accroche' => 'La Meute et ses sizaines vivent les grands jeux en forêt, au rythme des histoires du Livre de la jungle.',
		),
		'eclaireurs' => array(
			'nom'      => 'Éclaireurs',
			'ages'     => '12 – 16 ans',
			'logo'     => 'logo_Eclaireurs.png',
			'groupe'   => 'la Troupe',
			'staff'    => 'de la Troupe',
			'accroche' => 'La Troupe part à l’aventure en patrouilles : hikes, constructions, techniques et responsabilités partagées.',
		),
		'pionniers'  => array(
			'nom'      => 'Pionniers',
			'ages'     => '16 – 18 ans',
			'logo'     => 'logo_Pionniers.png',
			'groupe'   => 'le Poste',
			'staff'    => 'du Poste',
			'accroche' => 'Le Poste monte ses propres projets et s’engage, ici et ailleurs, avant de passer de l’autre côté : animateur.',
		),
	);
}

/**
 * Affiche la composition d'une page de section.
 *
 * @param string $slug Identifiant de la section.
 */
function scoutsunite_section_pattern( $slug ) {
	$sections = scoutsunite_sections();
	$s        = $sections[ $slug ];
	$nom      = $s['nom'];
	$groupe   = $s['groupe'];
	?>
<!-- wp:group {"align":"full","className":"is-style-bandeau-<?php echo esc_attr( $slug ); ?>","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"},"margin":{"top":"0","bottom":"0"}}},"layout":{"type":"constrained","contentSize":"78rem"}} -->
<div class="wp-block-group alignfull is-style-bandeau-<?php echo esc_attr( $slug ); ?>" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading {"level":1,"className":"panneau__titre"} -->
<h1 class="wp-block-heading panneau__titre"><?php echo esc_html( $nom ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"900","fontSize":"1.375rem"},"spacing":{"margin":{"top":"0.5rem"}}}} -->
<p style="margin-top:0.5rem;font-size:1.375rem;font-weight:900"><?php echo esc_html( $s['ages'] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"large","style":{"typography":{"fontWeight":"700"}}} -->
<p class="has-large-font-size" style="font-weight:700"><?php echo esc_html( $s['accroche'] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/inscription/">Inscrire mon enfant</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"14rem"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:14rem"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"bandeau__logo"} -->
<figure class="wp-block-image size-full bandeau__logo"><img src="<?php echo scoutsunite_image( 'logos/' . $s['logo'] ); ?>" alt="Logo des <?php echo esc_attr( $nom ); ?>"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"fond","layout":{"type":"constrained","contentSize":"78rem"}} -->
<div class="wp-block-group alignfull has-fond-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:columns {"className":"affiches","style":{"spacing":{"blockGap":{"left":"clamp(1.5rem, 4vw, 3rem)","top":"2.75rem"}}}} -->
<div class="wp-block-columns affiches"><!-- wp:column {"width":"55%"} -->
<div class="wp-block-column" style="flex-basis:55%"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:scoutsunite/prochaine-reunion {"section":"<?php echo esc_attr( $slug ); ?>"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-affiche","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-affiche"><!-- wp:heading {"level":2,"className":"affiche__titre","fontSize":"x-large"} -->
<h2 class="wp-block-heading affiche__titre has-x-large-font-size">Le staff <?php echo esc_html( $s['staff'] ); ?></h2>
<!-- /wp:heading -->

<!-- wp:scoutsunite/staff {"section":"<?php echo esc_attr( $slug ); ?>"} /-->

<!-- wp:paragraph {"className":"lien-fleche","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<p class="lien-fleche" style="margin-top:var(--wp--preset--spacing--40)"><a href="/contact/">Écrire au staff des <?php echo esc_html( $nom ); ?></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"},"margin":{"top":"0","bottom":"0"}}},"backgroundColor":"blanc","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-blanc-background-color has-background" style="margin-top:0;margin-bottom:0;padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading -->
<h2 class="wp-block-heading">L’année chez les <?php echo esc_html( $nom ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Raconte ici comment vit <?php echo esc_html( $groupe ); ?> : les réunions du samedi, les week-ends, les traditions de la section et ce que les enfants y apprennent.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Les temps forts</h3>
<!-- /wp:heading -->

<!-- wp:scoutsunite/camps {"section":"<?php echo esc_attr( $slug ); ?>"} /-->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Les documents</h3>
<!-- /wp:heading -->

<!-- wp:scoutsunite/documents {"section":"<?php echo esc_attr( $slug ); ?>"} /--></div>
<!-- /wp:group -->
	<?php
}
