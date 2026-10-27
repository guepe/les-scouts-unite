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

/**
 * Version d'un fichier du thème pour le cache des navigateurs : la version du thème,
 * suivie de la date du fichier, pour qu'une correction soit vue même sans changer de version.
 *
 * @param string $fichier Chemin relatif au thème.
 * @return string
 */
function scoutsunite_version( $fichier ) {
	$chemin = get_theme_file_path( $fichier );
	return wp_get_theme()->get( 'Version' ) . ( is_readable( $chemin ) ? '.' . filemtime( $chemin ) : '' );
}

function scoutsunite_enqueue() {
	wp_enqueue_style(
		'scoutsunite',
		get_stylesheet_uri(),
		array(),
		scoutsunite_version( 'style.css' )
	);
}
add_action( 'wp_enqueue_scripts', 'scoutsunite_enqueue' );

/**
 * Les deux polices de chaque page (le texte et les titres) partent tout de suite,
 * sans attendre que le navigateur les découvre dans le CSS.
 */
function scoutsunite_precharger_polices() {
	foreach ( array( 'Muli-Regular', 'Muli-Black' ) as $police ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( 'assets/fonts/' . $police . '.woff2' ) ) );
	}
}
add_action( 'wp_head', 'scoutsunite_precharger_polices', 1 );

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
 * Les compositions écrivent leurs liens depuis la racine (« /contact/ ») : ils sont recopiés tels quels
 * dans les pages créées. Sur un site installé dans un sous-dossier (exemple.be/scouts/), on y ajoute
 * le sous-dossier à l'affichage, pour les pages déjà créées comme pour les nouvelles.
 *
 * @param string $html Rendu d'un bloc.
 * @return string
 */
function scoutsunite_liens_racine( $html ) {
	if ( false === strpos( $html, 'href="/' ) ) {
		return $html;
	}
	$dossier = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );
	if ( '' === $dossier ) {
		return $html;
	}
	// Ni un lien « //autre-site », ni un lien qui porte déjà le sous-dossier.
	return preg_replace( '#href="/(?!/|' . preg_quote( ltrim( $dossier, '/' ), '#' ) . '(/|"))#', 'href="' . $dossier . '/', $html );
}
add_filter( 'render_block', 'scoutsunite_liens_racine' );

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
 * Reprise d'un site installé avec l'ancien thème scouts-11om-magnee : les pages
 * créées à partir de ses compositions gardent l'adresse de ses images (logos de
 * section…), dans un dossier qui n'existe plus. Une seule fois, on les fait
 * pointer vers ce thème, en https si le site l'est.
 */
function scoutsunite_reprise_images() {
	if ( get_option( 'scoutsunite_reprise_images' ) ) {
		return;
	}
	global $wpdb;

	$wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, '/themes/scouts-11om-magnee/', '/themes/les-scouts-unite/' ) WHERE post_content LIKE '%/themes/scouts-11om-magnee/%'" );

	if ( 'https' === wp_parse_url( home_url(), PHP_URL_SCHEME ) ) {
		$theme = '://' . wp_parse_url( home_url(), PHP_URL_HOST ) . '/wp-content/themes/les-scouts-unite/';
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s ) WHERE post_content LIKE %s",
				'http' . $theme,
				'https' . $theme,
				'%' . $wpdb->esc_like( 'http' . $theme ) . '%'
			)
		);
	}
	wp_cache_flush();

	update_option( 'scoutsunite_reprise_images', 1 );
}
add_action( 'init', 'scoutsunite_reprise_images', 20 );

/**
 * La page IAmA publiée, qu'elle soit à la racine (/iama/) ou rangée sous
 * « Les sections » (/sections/iama/).
 *
 * @return WP_Post|null
 */
function scoutsunite_page_iama() {
	foreach ( array( 'iama', 'sections/iama' ) as $chemin ) {
		$page = get_page_by_path( $chemin );
		if ( $page && 'publish' === $page->post_status ) {
			return $page;
		}
	}
	return null;
}

/**
 * Pages « pour les parents » du menu (Documents, Questions fréquentes), seulement
 * si l'unité les a créées et publiées : pas de lien vers une page qui n'existe pas.
 *
 * @return array Libellé => adresse.
 */
function scoutsunite_pages_parents() {
	$liens = array();
	foreach ( array( 'documents' => 'Documents', 'questions-frequentes' => 'Questions fréquentes' ) as $slug => $libelle ) {
		$page = get_page_by_path( $slug );
		if ( $page && 'publish' === $page->post_status ) {
			$liens[ $libelle ] = get_permalink( $page );
		}
	}
	return $liens;
}

/**
 * Sous la règle des âges : un mot pour les anciens, vers la page IAmA si elle
 * existe. Ajouté au rendu pour valoir aussi sur les pages déjà créées.
 *
 * @param string $html  Rendu du groupe.
 * @param array  $block Bloc.
 * @return string Rendu, suivi du paragraphe IAmA pour la règle des âges.
 */
function scoutsunite_regle_iama( $html, $block ) {
	if ( ! preg_match( '/(^|\s)regle-ages(\s|$)/', $block['attrs']['className'] ?? '' ) || is_admin() ) {
		return $html;
	}
	$page = scoutsunite_page_iama();
	if ( ! $page ) {
		return $html;
	}
	return $html . '<p class="regle-ages__iama">Et après 18 ans ? Les anciens et amis de l’unité gardent le lien dans le clan <a href="' . esc_url( get_permalink( $page ) ) . '">IAmA</a>.</p>';
}
add_filter( 'render_block_core/group', 'scoutsunite_regle_iama', 10, 2 );

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

	$ici = wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
	if ( trailingslashit( $lien['path'] ?? '' ) === trailingslashit( (string) $ici ) ) {
		return '';
	}

	return $html;
}
add_filter( 'render_block_core/paragraph', 'scoutsunite_lien_fleche' );

require get_template_directory() . '/inc/section.php';
require get_template_directory() . '/inc/agenda.php';
require get_template_directory() . '/inc/imprimer.php';
require get_template_directory() . '/inc/calendrier.php';
require get_template_directory() . '/inc/documents.php';
require get_template_directory() . '/inc/unite.php';
require get_template_directory() . '/inc/whatsapp.php';
require get_template_directory() . '/inc/droits.php';
