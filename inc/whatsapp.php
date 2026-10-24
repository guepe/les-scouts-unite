<?php
/**
 * Groupes WhatsApp de l'unité et de ses sections : un lien d'invitation par groupe,
 * encodé dans Réglages → Unité, affiché avec son QR code (page Contact, pages de
 * section, feuilles à imprimer). Le QR est dessiné en SVG sur le serveur, sans
 * service extérieur (inc/lib/qrcode.php).
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/lib/qrcode.php';

/**
 * QR code d'un texte, en SVG : un seul tracé, marge de 4 modules, mis à l'échelle par le CSS.
 *
 * @param string $texte Texte à encoder (un lien).
 * @param string $label Description pour les lecteurs d'écran.
 * @return string SVG.
 */
function scoutsunite_qr_svg( $texte, $label ) {
	$cle = 'qr_' . md5( $texte );
	$d   = wp_cache_get( $cle, 'scoutsunite' );
	if ( false === $d ) {
		$qr = new SU_QRCode();
		$qr->setErrorCorrectLevel( SU_QR_ERROR_CORRECT_LEVEL_M );
		$qr->addData( $texte, SU_QR_MODE_8BIT_BYTE );
		$longueur = $qr->getData( 0 )->getLength();
		for ( $version = 1; $version <= 40; $version++ ) {
			if ( $longueur <= SU_QRUtil::getMaxLength( $version, SU_QR_MODE_8BIT_BYTE, SU_QR_ERROR_CORRECT_LEVEL_M ) ) {
				$qr->setTypeNumber( $version );
				break;
			}
		}
		$qr->make();
		$n = $qr->getModuleCount();
		$d = array( $n, '' );
		for ( $r = 0; $r < $n; $r++ ) {
			for ( $c = 0; $c < $n; $c++ ) {
				if ( $qr->isDark( $r, $c ) ) {
					$d[1] .= 'M' . ( $c + 4 ) . ' ' . ( $r + 4 ) . 'h1v1h-1z';
				}
			}
		}
		wp_cache_set( $cle, $d, 'scoutsunite' );
	}
	$taille = $d[0] + 8;
	return '<svg class="qr" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $taille . ' ' . $taille . '" shape-rendering="crispEdges" role="img" aria-label="' . esc_attr( $label ) . '"><rect width="' . $taille . '" height="' . $taille . '" fill="#fff"/><path d="' . $d[1] . '" fill="#000"/></svg>';
}

/**
 * « des Baladins », « de l'unité », « du clan IAmA ».
 *
 * @param string $section Identifiant de section.
 * @return string
 */
function scoutsunite_whatsapp_de( $section ) {
	$noms = scoutsunite_agenda_sections();
	$de   = array(
		'unite' => 'de l’unité',
		'iama'  => 'du clan IAmA',
	);
	return $de[ $section ] ?? 'des ' . ( $noms[ $section ] ?? $section );
}

/**
 * Lien d'invitation d'un groupe, '' s'il n'est pas encodé.
 *
 * @param string $section Identifiant de section.
 * @return string URL.
 */
function scoutsunite_whatsapp_lien( $section ) {
	return scoutsunite_unite( 'whatsapp_' . $section );
}

/**
 * Une carte par groupe encodé : le QR code, le nom du groupe et le lien pour le rejoindre.
 *
 * @param string[] $sections Sections, dans l'ordre d'affichage.
 * @return string HTML, vide si aucun groupe n'est encodé.
 */
function scoutsunite_whatsapp_cartes( $sections ) {
	$html = '';
	foreach ( $sections as $section ) {
		$lien = scoutsunite_whatsapp_lien( $section );
		if ( '' === $lien ) {
			continue;
		}
		$nom   = 'Groupe WhatsApp ' . scoutsunite_whatsapp_de( $section );
		$html .= '<a class="whatsapp__groupe whatsapp--' . esc_attr( $section ) . '" href="' . esc_url( $lien ) . '">';
		$html .= scoutsunite_qr_svg( $lien, 'QR code du ' . $nom );
		$html .= '<span class="whatsapp__texte"><strong>' . esc_html( $nom ) . '</strong><span>Scanne le code ou clique pour rejoindre le groupe.</span></span></a>';
	}
	return $html;
}

/**
 * Bloc « Groupes WhatsApp » : ceux d'une section (avec celui de l'unité), ou tous.
 *
 * @param array $attributes Attributs du bloc (section, '' pour tous les groupes).
 * @return string HTML.
 */
function scoutsunite_bloc_whatsapp( $attributes ) {
	$section  = sanitize_key( $attributes['section'] ?? '' );
	$toutes   = array_keys( scoutsunite_agenda_sections() );
	$sections = in_array( $section, $toutes, true ) ? array_unique( array( $section, 'unite' ) ) : array_merge( array( 'unite' ), array_diff( $toutes, array( 'unite' ) ) );
	$cartes   = scoutsunite_whatsapp_cartes( $sections );
	if ( '' === $cartes ) {
		$cartes = '<p><mark class="a-completer">Ajoute les liens des groupes WhatsApp dans Réglages → Unité.</mark></p>';
	}
	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'whatsapp' ) ) . '>' . $cartes . '</div>';
}

function scoutsunite_whatsapp_bloc() {
	register_block_type(
		'scoutsunite/whatsapp',
		array(
			'api_version'     => 3,
			'title'           => 'Groupes WhatsApp',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'attributes'      => array(
				'section' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'render_callback' => 'scoutsunite_bloc_whatsapp',
		)
	);
}
add_action( 'init', 'scoutsunite_whatsapp_bloc' );
