<?php
/**
 * Thème d'une unité Les Scouts asbl, basé sur la charte graphique Les Scouts asbl.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Feuille de styles du thème, en front et dans l'éditeur.
 */
function scoutsunite_setup() {
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'scoutsunite_setup' );

function scoutsunite_enqueue() {
	wp_enqueue_style(
		'scoutsunite',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'scoutsunite_enqueue' );

/**
 * Styles de blocs : le panneau, les affiches et les lignes de section.
 */
function scoutsunite_block_styles() {
	register_block_style(
		'core/group',
		array(
			'name'  => 'panneau',
			'label' => __( 'Panneau (bleu + topping)', 'les-scouts-unite' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'affiche',
			'label' => __( 'Affiche épinglée', 'les-scouts-unite' ),
		)
	);
	register_block_style(
		'core/group',
		array(
			'name'  => 'affiche-verte',
			'label' => __( 'Affiche épinglée verte', 'les-scouts-unite' ),
		)
	);

	$sections = array(
		'baladins'   => 'Baladins',
		'louveteaux' => 'Louveteaux',
		'eclaireurs' => 'Éclaireurs',
		'pionniers'  => 'Pionniers',
	);
	foreach ( $sections as $slug => $label ) {
		register_block_style(
			'core/group',
			array(
				'name'  => 'ligne-' . $slug,
				/* translators: %s: nom de la section */
				'label' => sprintf( __( 'Ligne %s', 'les-scouts-unite' ), $label ),
			)
		);
		register_block_style(
			'core/group',
			array(
				'name'  => 'bandeau-' . $slug,
				/* translators: %s: nom de la section */
				'label' => sprintf( __( 'Bandeau %s', 'les-scouts-unite' ), $label ),
			)
		);
	}

	register_block_style(
		'core/paragraph',
		array(
			'name'  => 'manuscrit',
			'label' => __( 'Manuscrit', 'les-scouts-unite' ),
		)
	);
}
add_action( 'init', 'scoutsunite_block_styles' );

function scoutsunite_pattern_categories() {
	register_block_pattern_category(
		'scoutsunite',
		array( 'label' => __( 'Unité scoute', 'les-scouts-unite' ) )
	);
	register_block_pattern_category(
		'scoutsunite-pages',
		array( 'label' => __( 'Pages de l’unité', 'les-scouts-unite' ) )
	);
}
add_action( 'init', 'scoutsunite_pattern_categories' );

/**
 * Adresse d'une page de l'unité, pour les liens des compositions.
 *
 * @param string $slug Identifiant de la page.
 * @return string URL échappée.
 */
function scoutsunite_page_url( $slug ) {
	return esc_url( home_url( '/' . $slug . '/' ) );
}

/**
 * Adresse d'une image du thème.
 *
 * @param string $path Chemin relatif dans assets/images.
 * @return string URL échappée.
 */
function scoutsunite_image( $path ) {
	return esc_url( get_theme_file_uri( 'assets/images/' . $path ) );
}

/**
 * Icône du site par défaut : les silhouettes de la fédération, tant que l'unité
 * n'a pas choisi la sienne (icône du site dans les réglages WordPress). Sert aussi
 * /favicon.ico, que WordPress redirige vers cette adresse.
 *
 * @param string $url  Adresse calculée par WordPress.
 * @param int    $size Taille demandée, en pixels.
 * @return string Adresse de l'icône.
 */
function scoutsunite_site_icon( $url, $size ) {
	if ( get_option( 'site_icon' ) ) {
		return $url;
	}
	foreach ( array( 32, 180, 192, 270, 512 ) as $taille ) {
		if ( $size <= $taille ) {
			break;
		}
	}
	return scoutsunite_image( 'favicon/favicon-' . $taille . '.png' );
}
add_filter( 'get_site_icon_url', 'scoutsunite_site_icon', 10, 2 );

/**
 * Retire un lien fléché qui mène à la page affichée : la règle des âges
 * propose « Découvrir les sections », inutile sur la page Les sections.
 *
 * @param string $html Rendu du paragraphe.
 * @return string Rendu, vide si le lien pointe vers la page courante.
 */
function scoutsunite_lien_fleche( $html ) {
	if ( is_admin() || false === strpos( $html, 'lien-fleche' ) ) {
		return $html;
	}

	$tags = new WP_HTML_Tag_Processor( $html );
	if ( ! $tags->next_tag( 'a' ) ) {
		return $html;
	}

	$lien = wp_parse_url( (string) $tags->get_attribute( 'href' ) );
	$hote = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( ! empty( $lien['host'] ) && $lien['host'] !== $hote ) {
		return $html;
	}

	$ici = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH );
	if ( trailingslashit( $lien['path'] ?? '' ) === trailingslashit( (string) $ici ) ) {
		return '';
	}

	return $html;
}
add_filter( 'render_block_core/paragraph', 'scoutsunite_lien_fleche' );

require get_template_directory() . '/inc/section.php';
require get_template_directory() . '/inc/agenda.php';
require get_template_directory() . '/inc/unite.php';
