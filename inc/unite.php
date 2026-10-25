<?php
/**
 * Coordonnées de l'unité : encodées une fois dans Réglages → Unité,
 * affichées partout (pied de page, contact, infos pratiques, inscription).
 *
 * Les blocs des compositions sont liés à ces réglages par les liaisons de blocs
 * de WordPress (source « scoutsunite/unite », argument « key »). Un champ vide
 * garde son exemple surligné « à compléter ».
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Champs de la page de réglages, par groupe : clé => [ libellé, type, exemple ].
 *
 * @return array
 */
function scoutsunite_unite_champs() {
	$champs = array(
		'Le local'          => array(
			'local_rue'      => array( 'Rue et numéro', 'text', 'Rue du Local 1' ),
			'local_localite' => array( 'Code postal et localité', 'text', '0000 Localité' ),
		),
		'Le staff d’unité'  => array(
			'ru_nom'       => array( 'Responsable(s) d’unité', 'text', 'Prénom Nom' ),
			'ru_telephone' => array( 'Téléphone', 'tel', '0400 00 00 00' ),
			'staff_email'  => array( 'E-mail du staff d’unité', 'email', 'staff@example.org' ),
		),
		'Les staffs de section' => array(),
		'Les groupes WhatsApp'  => array(),
		'Bon à savoir'          => array(),
		'Le clan IAmA'      => array(
			'iama_chef'  => array( 'Responsable IAmA de l’unité', 'text', 'Prénom Nom' ),
			'iama_email' => array( 'E-mail du clan IAmA', 'email', 'iama@example.org' ),
		),
		'Les formulaires'   => array(
			'formulaire_inscription' => array( 'Lien du formulaire d’inscription', 'url', 'https://…' ),
			'formulaire_contact'     => array( 'Lien du formulaire de contact', 'url', 'https://…' ),
		),
		'Les inscriptions'  => array(
			'cotisation' => array( 'Cotisation (un tarif par ligne)', 'textarea', "80 € pour le 1er enfant\n70 € par enfant pour 2 enfants dans l’unité\n65 € par enfant, pour plus de 2 enfants" ),
			'compte'     => array( 'Numéro de compte (IBAN)', 'text', 'BE00 0000 0000 0000' ),
			'places'     => array( 'Places disponibles (affiche de l’accueil)', 'text', 'Places disponibles : à préciser par le staff.' ),
		),
	);
	foreach ( scoutsunite_agenda_sections_age() as $slug => $nom ) {
		$champs['Les staffs de section'][ $slug . '_chef' ]  = array( $nom . ' : chef ou cheffe de section', 'text', 'Totem – Prénom (fonction) 0470 00 00 00' );
		$champs['Les staffs de section'][ $slug . '_staff' ] = array( $nom . ' : animateurs (un par ligne)', 'textarea', "Totem – Prénom (santé) 0470 00 00 00\nTotem – Prénom (communication)" );
		$champs['Les staffs de section'][ $slug . '_email' ] = array( $nom . ' : e-mail', 'email', $slug . '@example.org' );
		$champs['Les staffs de section'][ $slug . '_gsm' ]   = array( $nom . ' : GSM de la section', 'tel', '0470 00 00 00' );
		$champs['Bon à savoir'][ $slug . '_infos' ]          = array( $nom . ' (une info par ligne)', 'textarea', "Réunions : le samedi de 14 h à 17 h, au local\nUniforme : pull de la section, foulard, short ou jupe bleu marine\nÀ emporter : une gourde et un goûter" );
	}
	foreach ( scoutsunite_agenda_sections() as $slug => $nom ) {
		$champs['Les groupes WhatsApp'][ 'whatsapp_' . $slug ] = array( 'unite' === $slug ? 'Groupe de toute l’unité' : 'Groupe ' . scoutsunite_whatsapp_de( $slug ), 'url', 'https://chat.whatsapp.com/…' );
	}
	return $champs;
}

/**
 * Valeur brute d'un champ, '' s'il n'est pas rempli.
 *
 * @param string $cle Clé du champ.
 * @return string
 */
function scoutsunite_unite( $cle ) {
	$valeurs = get_option( 'scoutsunite_unite', array() );
	return is_array( $valeurs ) && isset( $valeurs[ $cle ] ) ? trim( (string) $valeurs[ $cle ] ) : '';
}

/**
 * Valeur échappée, ou son exemple surligné « à compléter ».
 *
 * @param string $cle Clé du champ.
 * @return string HTML.
 */
function scoutsunite_unite_texte( $cle ) {
	$valeur = scoutsunite_unite( $cle );
	if ( '' !== $valeur ) {
		return esc_html( $valeur );
	}
	foreach ( scoutsunite_unite_champs() as $groupe ) {
		if ( isset( $groupe[ $cle ] ) ) {
			return '<mark class="a-completer">' . esc_html( $groupe[ $cle ][2] ) . '</mark>';
		}
	}
	return '';
}

/**
 * Téléphone ou e-mail en lien cliquable ; l'exemple reste sans lien.
 *
 * @param string $cle Clé du champ.
 * @param string $schema 'tel' ou 'mailto'.
 * @return string HTML.
 */
function scoutsunite_unite_lien( $cle, $schema ) {
	$valeur = scoutsunite_unite( $cle );
	if ( '' === $valeur ) {
		return scoutsunite_unite_texte( $cle );
	}
	$cible = 'tel' === $schema ? preg_replace( '/[^\d+]/', '', $valeur ) : $valeur;
	// Une adresse trop longue pour sa colonne passe à la ligne après l'arobase, pas au milieu d'un mot.
	$texte = 'mailto' === $schema ? str_replace( '@', '@<wbr>', esc_html( $valeur ) ) : esc_html( $valeur );
	return '<a href="' . esc_url( $schema . ':' . $cible, array( $schema ) ) . '">' . $texte . '</a>';
}

/**
 * Contact d'une section : son e-mail, son GSM ou les deux, en liens. Sans l'un ni
 * l'autre, l'exemple surligné « à compléter ».
 *
 * @param string $section Section d'âge.
 * @return string HTML.
 */
function scoutsunite_unite_contact_section( $section ) {
	$liens = array();
	if ( '' !== scoutsunite_unite( $section . '_email' ) ) {
		$liens[] = scoutsunite_unite_lien( $section . '_email', 'mailto' );
	}
	if ( '' !== scoutsunite_unite( $section . '_gsm' ) ) {
		$liens[] = scoutsunite_unite_lien( $section . '_gsm', 'tel' );
	}
	// Seules les sections d'âge ont un GSM (le clan IAmA n'a qu'un e-mail).
	$gsm = isset( scoutsunite_sections()[ $section ] ) ? ' ou 0470 00 00 00' : '';
	return $liens ? implode( '<br>', $liens ) : '<mark class="a-completer">' . esc_html( $section . '@example.org' . $gsm ) . '</mark>';
}

/**
 * Contenu d'un bloc lié à une clé.
 *
 * @param string $cle Clé de liaison.
 * @return string HTML.
 */
function scoutsunite_unite_html( $cle ) {
	switch ( $cle ) {
		case 'adresse':
			return scoutsunite_unite_texte( 'local_rue' ) . '<br>' . scoutsunite_unite_texte( 'local_localite' );
		case 'itineraire':
			$adresse = trim( scoutsunite_unite( 'local_rue' ) . ', ' . scoutsunite_unite( 'local_localite' ), ', ' );
			if ( '' === $adresse ) {
				return '<mark class="a-completer">Itinéraire : ajoute l’adresse du local dans Réglages → Unité</mark>';
			}
			return '<a href="' . esc_url( 'https://www.openstreetmap.org/search?query=' . rawurlencode( $adresse . ', Belgique' ) ) . '">Itinéraire</a>';
		case 'staff-unite':
			return '<strong>' . scoutsunite_unite_texte( 'ru_nom' ) . '</strong><br>' . scoutsunite_unite_lien( 'ru_telephone', 'tel' ) . '<br>' . scoutsunite_unite_lien( 'staff_email', 'mailto' );
		case 'responsable':
			return '<strong>' . scoutsunite_unite_texte( 'ru_nom' ) . '</strong>, responsable d’unité<br>' . scoutsunite_unite_lien( 'ru_telephone', 'tel' );
		case 'email-staff':
			return scoutsunite_unite_lien( 'staff_email', 'mailto' );
		case 'cotisation':
			$tarifs = array_filter( array_map( 'trim', explode( "\n", scoutsunite_unite( 'cotisation' ) ) ) );
			if ( $tarifs ) {
				// Le montant en tête de ligne passe en gras : « 80 € pour le 1er enfant ».
				$lignes = array_map(
					function ( $ligne ) {
						return preg_replace( '/^([\d\s.,]+\s?€)/u', '<strong>$1</strong>', esc_html( $ligne ) );
					},
					$tarifs
				);
			} else {
				$lignes = array( '<mark class="a-completer">… € par enfant</mark>' );
			}
			return implode( '<br>', $lignes ) . '<br>À verser sur le compte <strong>' . scoutsunite_unite_texte( 'compte' ) . '</strong> avec le nom de l’enfant en communication.';
		case 'places':
			return scoutsunite_unite_texte( 'places' );
		case 'iama-contact':
			return '<strong>' . scoutsunite_unite_texte( 'iama_chef' ) . '</strong>, responsable IAmA de l’unité<br>' . scoutsunite_unite_lien( 'iama_email', 'mailto' );
		case 'formulaire-inscription':
		case 'formulaire-contact':
			$champ = str_replace( '-', '_', $cle );
			$lien  = scoutsunite_unite( $champ );
			if ( '' === $lien ) {
				return 'formulaire-inscription' === $cle
					? '<mark class="a-completer">Ajoute le lien du formulaire d’inscription dans Réglages → Unité.</mark>'
					: '<mark class="a-completer">Ajoute le lien du formulaire de contact dans Réglages → Unité.</mark>';
			}
			$texte = 'formulaire-inscription' === $cle ? 'Remplir le formulaire d’inscription' : 'Écrire via le formulaire de contact';
			return '<a class="wp-block-button__link wp-element-button" href="' . esc_url( $lien ) . '">' . $texte . '</a>';
	}
	// Staffs de section : « baladins-chef », « baladins-email » (l'e-mail et/ou le GSM de la section)…
	if ( preg_match( '/^([a-z]+)-(chef|email)$/', $cle, $m ) ) {
		return 'chef' === $m[2] ? scoutsunite_unite_texte( $m[1] . '_chef' ) : scoutsunite_unite_contact_section( $m[1] );
	}
	return '';
}

/**
 * Toutes les clés de liaison, pour l'aperçu dans l'éditeur.
 *
 * @return array Clé => HTML.
 */
function scoutsunite_unite_toutes() {
	$cles = array( 'adresse', 'itineraire', 'staff-unite', 'responsable', 'email-staff', 'cotisation', 'places', 'formulaire-inscription', 'formulaire-contact', 'iama-contact', 'iama-chef', 'iama-email' );
	foreach ( array_keys( scoutsunite_agenda_sections_age() ) as $slug ) {
		$cles[] = $slug . '-chef';
		$cles[] = $slug . '-email';
	}
	$html = array();
	foreach ( $cles as $cle ) {
		$html[ $cle ] = scoutsunite_unite_html( $cle );
	}
	return $html;
}

/**
 * « Akéla – Marie (santé, communication) 0470 12 34 56 » → [ 'Akéla', 'Marie', 'santé, communication', '0470 12 34 56' ].
 * La fonction entre parenthèses et le GSM sont facultatifs ; sans tiret ni virgule, un prénom seul.
 *
 * @param string $ligne Une ligne du staff.
 * @return array Totem, prénom, fonction, GSM.
 */
function scoutsunite_unite_personne( $ligne ) {
	$ligne    = trim( $ligne );
	$fonction = '';
	$gsm      = '';
	if ( preg_match( '/\(([^)]*)\)/u', $ligne, $m ) ) {
		$fonction = trim( $m[1] );
		$ligne    = str_replace( $m[0], ' ', $ligne );
	}
	// Un numéro belge ou international : « 0470 12 34 56 », « 0470/12.34.56 », « +32 470 12 34 56 ».
	if ( preg_match( '/(?:\+|\b0)\d[\d .\/]{6,}\d/u', $ligne, $m ) ) {
		$gsm   = trim( $m[0] );
		$ligne = str_replace( $m[0], ' ', $ligne );
	}
	$ligne = preg_replace( array( '/\s+/u', '/^[\s–—,·-]+|[\s–—,·-]+$/u' ), array( ' ', '' ), $ligne );
	$parts = preg_split( '/\s+[–—-]\s+|\s*,\s*/u', $ligne, 2 );
	return 2 === count( $parts )
		? array( trim( $parts[0] ), trim( $parts[1] ), $fonction, $gsm )
		: array( '', trim( $parts[0] ), $fonction, $gsm );
}

/**
 * « Bon à savoir » d'une section : ce qui ne change pas d'une semaine à l'autre, une info par ligne.
 * « Uniforme : pull, foulard » met « Uniforme » en gras.
 *
 * @param string $section Section d'âge.
 * @param bool   $exemple Sans info encodée, montrer l'exemple surligné « à compléter » (sur le site, pas sur papier).
 * @return string HTML de la liste, '' si rien à montrer.
 */
function scoutsunite_bon_a_savoir( $section, $exemple = true ) {
	if ( ! isset( scoutsunite_sections()[ $section ] ) ) {
		return '';
	}
	$lignes = array_filter( array_map( 'trim', explode( "\n", scoutsunite_unite( $section . '_infos' ) ) ) );
	$vide   = ! $lignes;
	if ( $vide ) {
		if ( ! $exemple ) {
			return '';
		}
		$lignes = explode( "\n", scoutsunite_unite_champs()['Bon à savoir'][ $section . '_infos' ][2] );
	}
	$html = '';
	foreach ( $lignes as $ligne ) {
		$parts = preg_split( '/\s*:\s*/u', $ligne, 2 );
		$texte = 2 === count( $parts ) && '' !== $parts[0] ? '<strong>' . esc_html( $parts[0] ) . '</strong> ' . esc_html( $parts[1] ) : esc_html( $ligne );
		$html .= '<li>' . ( $vide ? '<mark class="a-completer">' . $texte . '</mark>' : $texte ) . '</li>';
	}
	return '<ul class="bon-a-savoir__liste">' . $html . '</ul>';
}

/**
 * GSM en lien cliquable.
 *
 * @param string $gsm Numéro tel qu'encodé.
 * @return string HTML.
 */
function scoutsunite_unite_gsm( $gsm ) {
	return '<a href="' . esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', $gsm ), array( 'tel' ) ) . '">' . esc_html( $gsm ) . '</a>';
}

/**
 * Page de section : le staff, chef en tête, chacun avec son totem et son prénom.
 *
 * @param array $attributes Attributs du bloc (section).
 * @return string HTML.
 */
function scoutsunite_bloc_staff( $attributes ) {
	$section = sanitize_key( $attributes['section'] ?? 'baladins' );
	$chef    = scoutsunite_unite( $section . '_chef' );
	$autres  = array_filter( array_map( 'trim', explode( "\n", scoutsunite_unite( $section . '_staff' ) ) ) );

	$membre = function ( $totem, $prenom, $est_chef, $exemple = false, $fonction = '', $gsm = '' ) {
		$initiale = mb_strtoupper( mb_substr( '' !== $totem ? $totem : $prenom, 0, 1 ) );
		$texte    = function ( $t ) use ( $exemple ) {
			return $exemple ? '<mark class="a-completer">' . esc_html( $t ) . '</mark>' : esc_html( $t );
		};
		$html  = '<li class="staff__membre' . ( $est_chef ? ' staff__membre--chef' : '' ) . '">';
		$html .= '<span class="staff__pastille" aria-hidden="true">' . esc_html( $initiale ) . '</span>';
		$html .= '<span class="staff__noms">';
		if ( '' !== $totem ) {
			$html .= '<span class="staff__totem">' . $texte( $totem ) . '</span>';
		}
		$html .= '<span class="staff__prenom">' . $texte( $prenom ) . '</span>';
		if ( $est_chef ) {
			$html .= '<span class="staff__role">chef de section</span>';
		}
		if ( '' !== $fonction ) {
			$html .= '<span class="staff__fonction">' . esc_html( $fonction ) . '</span>';
		}
		if ( '' !== $gsm ) {
			$html .= '<span class="staff__gsm">' . scoutsunite_unite_gsm( $gsm ) . '</span>';
		}
		return $html . '</span></li>';
	};

	$liste = '';
	if ( '' === $chef && ! $autres ) {
		$liste .= $membre( 'Totem', 'Prénom', true, true ) . $membre( 'Totem', 'Prénom', false, true ) . $membre( 'Totem', 'Prénom', false, true );
		$total  = '';
	} else {
		if ( '' !== $chef ) {
			list( $totem, $prenom, $fonction, $gsm ) = scoutsunite_unite_personne( $chef );
			$liste .= $membre( $totem, $prenom, true, false, $fonction, $gsm );
		} else {
			$liste .= $membre( 'Totem', 'Prénom', true, true );
		}
		foreach ( $autres as $ligne ) {
			list( $totem, $prenom, $fonction, $gsm ) = scoutsunite_unite_personne( $ligne );
			$liste .= $membre( $totem, $prenom, false, false, $fonction, $gsm );
		}
		$nombre = count( $autres ) + ( '' !== $chef ? 1 : 0 );
		$total  = '<p class="staff__total">' . $nombre . ( 1 === $nombre ? ' animateur' : ' animateurs' ) . '</p>';
	}

	$wrapper = get_block_wrapper_attributes(
		array(
			'class' => 'staff',
			'style' => '--section: var(--wp--preset--color--' . $section . ');',
		)
	);
	// Le contact de la section (e-mail et/ou GSM), s'il est rempli : sur la page Contact, il garde son exemple.
	$contact = '';
	if ( '' !== scoutsunite_unite( $section . '_email' ) || '' !== scoutsunite_unite( $section . '_gsm' ) ) {
		$contact = '<p class="staff__contact"><span>Pour joindre la section :</span> ' . scoutsunite_unite_contact_section( $section ) . '</p>';
	}
	$total .= $contact;

	$whatsapp = scoutsunite_whatsapp_cartes( array( $section ) );
	if ( '' !== $whatsapp ) {
		$whatsapp = '<div class="whatsapp">' . $whatsapp . '</div>';
	}
	return '<div ' . $wrapper . '><ul class="staff__liste">' . $liste . '</ul>' . $total . $whatsapp . '</div>';
}

/**
 * Reprise des pages de section déjà créées : la liste d'exemple du staff (encore
 * surlignée « à compléter ») devient le bloc « Staff de la section ». Une liste
 * déjà remplie à la main n'est pas touchée.
 */
function scoutsunite_reprise_staff() {
	if ( get_option( 'scoutsunite_reprise_staff' ) ) {
		return;
	}
	global $wpdb;

	$sections = array_keys( scoutsunite_sections() );
	$pages    = $wpdb->get_results( "SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_type = 'page' AND post_content LIKE '%chef de section</li>%'" );
	foreach ( $pages as $page ) {
		$section = in_array( $page->post_name, $sections, true ) ? $page->post_name : '';
		if ( ! $section && preg_match( '/prochaine-reunion \{"section":"([a-z]+)"/', $page->post_content, $m ) ) {
			$section = $m[1];
		}
		if ( ! $section ) {
			continue;
		}
		$contenu = preg_replace_callback(
			'#<!-- wp:list(?: \{[^\n]*?\})? -->(?:(?!<!-- /wp:list -->).)*?chef de section</li>(?:(?!<!-- /wp:list -->).)*?<!-- /wp:list -->#s',
			function ( $liste ) use ( $section ) {
				return false === strpos( $liste[0], 'a-completer' ) ? $liste[0] : '<!-- wp:scoutsunite/staff {"section":"' . $section . '"} /-->';
			},
			$page->post_content
		);
		$contenu = str_replace( 'Le staff de le Poste', 'Le staff du Poste', $contenu );
		if ( $contenu !== $page->post_content ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $contenu ), array( 'ID' => $page->ID ) );
			clean_post_cache( $page->ID );
		}
	}

	update_option( 'scoutsunite_reprise_staff', 1 );
}
add_action( 'init', 'scoutsunite_reprise_staff', 20 );

/**
 * Reprise des pages de section déjà créées : le texte d'exemple du camp d'été
 * devient le bloc « Week-ends et camp », alimenté par l'agenda.
 */
function scoutsunite_reprise_camps() {
	if ( get_option( 'scoutsunite_reprise_camps' ) ) {
		return;
	}
	global $wpdb;

	$sections = array_keys( scoutsunite_sections() );
	$pages    = $wpdb->get_results( "SELECT ID, post_name, post_content FROM {$wpdb->posts} WHERE post_type = 'page' AND post_content LIKE '%Dates, lieu, prix et liste du matériel%'" );
	foreach ( $pages as $page ) {
		$section = in_array( $page->post_name, $sections, true ) ? $page->post_name : '';
		if ( ! $section && preg_match( '/(?:prochaine-reunion|staff) \{"section":"([a-z]+)"/', $page->post_content, $m ) ) {
			$section = $m[1];
		}
		if ( ! $section ) {
			continue;
		}
		$contenu = preg_replace(
			'#<!-- wp:paragraph -->\s*<p[^>]*>Dates, lieu, prix et liste du matériel : ajoute-les dès qu’ils sont connus\.</p>\s*<!-- /wp:paragraph -->#u',
			'<!-- wp:scoutsunite/camps {"section":"' . $section . '"} /-->',
			$page->post_content
		);
		$contenu = str_replace( 'Le camp d’été</h3>', 'Les temps forts</h3>', $contenu );
		if ( $contenu !== $page->post_content ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $contenu ), array( 'ID' => $page->ID ) );
			clean_post_cache( $page->ID );
		}
	}

	update_option( 'scoutsunite_reprise_camps', 1 );
}
add_action( 'init', 'scoutsunite_reprise_camps', 20 );

/**
 * Reprise : le titre « Week-ends et camp » devient « Les temps forts », qui
 * couvre aussi les réunions spéciales.
 */
function scoutsunite_reprise_temps_forts() {
	if ( get_option( 'scoutsunite_reprise_temps_forts' ) ) {
		return;
	}
	global $wpdb;
	$wpdb->query( "UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, 'Week-ends et camp</h3>', 'Les temps forts</h3>' ) WHERE post_type = 'page' AND post_content LIKE '%Week-ends et camp</h3>%'" );
	wp_cache_flush();
	update_option( 'scoutsunite_reprise_temps_forts', 1 );
}
add_action( 'init', 'scoutsunite_reprise_temps_forts', 20 );

function scoutsunite_unite_bloc() {
	register_block_type(
		'scoutsunite/staff',
		array(
			'api_version'     => 3,
			'title'           => 'Staff de la section',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'attributes'      => array(
				'section' => array(
					'type'    => 'string',
					'default' => 'baladins',
				),
			),
			'render_callback' => 'scoutsunite_bloc_staff',
		)
	);
}
add_action( 'init', 'scoutsunite_unite_bloc' );

function scoutsunite_unite_bindings() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}
	register_block_bindings_source(
		'scoutsunite/unite',
		array(
			'label'              => 'Coordonnées de l’unité',
			'get_value_callback' => function ( $args ) {
				return scoutsunite_unite_html( $args['key'] ?? '' );
			},
		)
	);
}
add_action( 'init', 'scoutsunite_unite_bindings' );

/**
 * L'éditeur montre les vraies valeurs à la place des exemples.
 */
function scoutsunite_unite_editeur() {
	wp_add_inline_script(
		'scoutsunite-blocs',
		'window.scoutsuniteUnite = ' . wp_json_encode( scoutsunite_unite_toutes() ) . ';'
		. 'window.scoutsuniteSectionsBlocs = ' . wp_json_encode( scoutsunite_agenda_sections_age() ) . ';'
		. 'window.scoutsuniteGroupes = ' . wp_json_encode( scoutsunite_agenda_sections() ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'scoutsunite_unite_editeur' );

/* ---------------------------------------------------------------------------
   Réglages → Unité
   ------------------------------------------------------------------------ */

function scoutsunite_unite_reglages() {
	register_setting(
		'scoutsunite_unite',
		'scoutsunite_unite',
		array(
			'type'              => 'array',
			'default'           => array(),
			'sanitize_callback' => function ( $valeurs ) {
				$propre = array();
				foreach ( scoutsunite_unite_champs() as $groupe ) {
					foreach ( $groupe as $cle => $champ ) {
						$v              = isset( $valeurs[ $cle ] ) ? wp_unslash( $valeurs[ $cle ] ) : '';
						if ( 'email' === $champ[1] ) {
							$propre[ $cle ] = sanitize_email( $v );
						} elseif ( 'url' === $champ[1] ) {
							$propre[ $cle ] = esc_url_raw( $v );
							if ( 0 === strpos( $cle, 'whatsapp_' ) && strlen( $propre[ $cle ] ) > scoutsunite_qr_longueur_max() ) {
								add_settings_error( 'scoutsunite_unite', $cle, $champ[0] . ' : ce lien est trop long pour un QR code. Le lien s’affiche, sans QR code.', 'warning' );
							}
						} elseif ( 'textarea' === $champ[1] ) {
							$propre[ $cle ] = sanitize_textarea_field( $v );
						} else {
							$propre[ $cle ] = sanitize_text_field( $v );
						}
					}
				}
				return $propre;
			},
		)
	);
}
add_action( 'admin_init', 'scoutsunite_unite_reglages' );

function scoutsunite_unite_menu() {
	add_options_page( 'Coordonnées de l’unité', 'Unité', 'manage_unite', 'scoutsunite-unite', 'scoutsunite_unite_page' );
}

/**
 * Le staff d'unité enregistre ces réglages sans être administrateur.
 */
add_filter(
	'option_page_capability_scoutsunite_unite',
	function () {
		return 'manage_unite';
	}
);
add_action( 'admin_menu', 'scoutsunite_unite_menu' );

function scoutsunite_unite_page() {
	?>
	<div class="wrap">
		<h1>Coordonnées de l’unité</h1>
		<p>Encode ces informations une seule fois : elles s’affichent d’elles-mêmes dans le pied de page, les infos pratiques de l’accueil, la page Contact et la page Inscription. Les liens des formulaires (Google Forms, extension de formulaire…) deviennent des boutons sur ces deux pages.
			Un champ laissé vide garde son exemple surligné en orange sur le site, pour qu’on voie ce qu’il reste à compléter.</p>
		<p>Pour les staffs, écris une personne par ligne sous la forme « Totem – Prénom » (un prénom seul si la personne n’a pas encore de totem). Tu peux ajouter sa fonction entre parenthèses et son GSM : « Castor – Marie (santé, communication) 0470 12 34 56 ». Le chef ou la cheffe de section s’affiche en premier sur la page de la section. Les GSM sont visibles par tout le monde sur le site et sur les feuilles à imprimer. Pour joindre une section, remplis son e-mail, son GSM ou les deux : la page Contact et les feuilles à imprimer montrent ce qui est rempli.</p>
		<p>« Bon à savoir » : ce qui ne change pas d’une semaine à l’autre (horaire habituel, lieu, uniforme, à emporter), une info par ligne. Commence une ligne par un mot suivi de deux-points pour le mettre en gras : « Uniforme : pull de la section, foulard ». Les infos s’affichent sous la prochaine réunion, sur la page de la section, et sur les feuilles à imprimer.</p>
		<p>Pour un groupe WhatsApp, colle son lien d’invitation (dans WhatsApp : infos du groupe → Inviter via un lien). Le site en fait un QR code, affiché sur la page Contact, la page de la section et les feuilles à imprimer. Toute personne qui a le lien peut rejoindre le groupe : garde l’approbation des nouveaux membres activée dans WhatsApp.</p>
		<p>Le nom de l’unité se règle dans <a href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">Réglages → Général</a> (titre et slogan du site).</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'scoutsunite_unite' ); ?>
			<?php foreach ( scoutsunite_unite_champs() as $titre => $groupe ) : ?>
				<h2><?php echo esc_html( $titre ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( $groupe as $cle => $champ ) : ?>
						<tr>
							<th scope="row"><label for="su-<?php echo esc_attr( $cle ); ?>"><?php echo esc_html( $champ[0] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $champ[1] ) : ?>
									<textarea class="large-text" rows="4" id="su-<?php echo esc_attr( $cle ); ?>" name="scoutsunite_unite[<?php echo esc_attr( $cle ); ?>]" placeholder="<?php echo esc_attr( $champ[2] ); ?>"><?php echo esc_textarea( scoutsunite_unite( $cle ) ); ?></textarea>
								<?php else : ?>
									<input class="regular-text" type="<?php echo esc_attr( $champ[1] ); ?>" id="su-<?php echo esc_attr( $cle ); ?>" name="scoutsunite_unite[<?php echo esc_attr( $cle ); ?>]" value="<?php echo esc_attr( scoutsunite_unite( $cle ) ); ?>" placeholder="<?php echo esc_attr( $champ[2] ); ?>">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>
			<?php submit_button( 'Enregistrer les coordonnées' ); ?>
		</form>
	</div>
	<?php
}
