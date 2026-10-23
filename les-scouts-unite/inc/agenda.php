<?php
/**
 * Agenda de l'unité : le type de contenu « Réunion » et les blocs qui l'affichent.
 *
 * Les animateurs encodent les réunions dans l'administration (Réunions → Ajouter).
 * L'accueil, les pages de section et la page /agenda/ se mettent à jour seuls :
 * les réunions passées disparaissent, la suivante prend leur place.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sections pouvant organiser une réunion, dans l'ordre des âges.
 *
 * @return array Identifiant => nom affiché.
 */
function scoutsunite_agenda_sections() {
	return array(
		'baladins'   => 'Baladins',
		'louveteaux' => 'Louveteaux',
		'eclaireurs' => 'Éclaireurs',
		'pionniers'  => 'Pionniers',
		'unite'      => 'Toute l’unité',
	);
}

/* ---------------------------------------------------------------------------
   Type de contenu et champs
   ------------------------------------------------------------------------ */

function scoutsunite_agenda_register() {
	register_post_type(
		'reunion',
		array(
			'labels'        => array(
				'name'               => 'Réunions',
				'singular_name'      => 'Réunion',
				'menu_name'          => 'Agenda',
				'all_items'          => 'Toutes les réunions',
				'add_new'            => 'Ajouter',
				'add_new_item'       => 'Ajouter une réunion',
				'edit_item'          => 'Modifier la réunion',
				'new_item'           => 'Nouvelle réunion',
				'view_item'          => 'Voir la réunion',
				'search_items'       => 'Rechercher une réunion',
				'not_found'          => 'Aucune réunion',
				'not_found_in_trash' => 'Aucune réunion dans la corbeille',
				'archives'           => 'Agenda',
			),
			'public'        => true,
			'has_archive'   => 'agenda',
			'rewrite'       => array(
				'slug'       => 'agenda',
				'with_front' => false,
			),
			'menu_icon'     => 'dashicons-calendar-alt',
			'menu_position' => 5,
			'show_in_rest'  => true,
			'supports'      => array( 'title', 'editor', 'revisions', 'custom-fields' ),
			'template'      => array(
				array(
					'core/paragraph',
					array( 'placeholder' => 'Notes pour les parents : à emporter, rendez-vous, remarques… (facultatif)' ),
				),
			),
		)
	);

	foreach ( array( 'su_section', 'su_date', 'su_debut', 'su_fin', 'su_lieu', 'su_repeter', 'su_avant' ) as $key ) {
		register_post_meta(
			'reunion',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'su_lieu' === $key ? 'au local' : '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'scoutsunite_agenda_register' );

/**
 * Les adresses /agenda/ doivent exister dès l'activation du thème.
 */
function scoutsunite_agenda_flush() {
	scoutsunite_agenda_register();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'scoutsunite_agenda_flush' );

/**
 * Reprise d'un site installé avec l'ancien thème scouts-11om-magnee : ses réunions
 * gardaient leurs champs sous s11_* et ses pages appelaient les blocs scouts11om/*.
 * Une seule fois, on recopie les champs vers su_* (sans toucher aux anciens, ni à
 * un champ su_* déjà rempli) et on renomme les blocs dans les contenus.
 */
function scoutsunite_agenda_reprise() {
	if ( get_option( 'scoutsunite_reprise_s11' ) ) {
		return;
	}
	global $wpdb;

	$cles = array( 'section', 'date', 'debut', 'fin', 'lieu', 'repeter' );
	$ids  = $wpdb->get_col( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key LIKE 's11\_%' OR meta_key = '_s11_repete_jusqua'" );
	foreach ( $ids as $id ) {
		foreach ( array_merge( $cles, array( 'repete_jusqua' ) ) as $cle ) {
			$prefixe = 'repete_jusqua' === $cle ? '_' : '';
			$ancien  = get_metadata_raw( 'post', $id, $prefixe . 's11_' . $cle, true );
			$nouveau = get_metadata_raw( 'post', $id, $prefixe . 'su_' . $cle, true );
			if ( null !== $ancien && '' !== $ancien && ( null === $nouveau || '' === $nouveau ) ) {
				update_post_meta( $id, $prefixe . 'su_' . $cle, $ancien );
			}
		}
	}

	$wpdb->query(
		"UPDATE {$wpdb->posts}
		SET post_content = REPLACE( REPLACE( post_content, 'wp:scouts11om/', 'wp:scoutsunite/' ), '\"slug\":\"scouts-11om-magnee/', '\"slug\":\"les-scouts-unite/' )
		WHERE post_content LIKE '%wp:scouts11om/%' OR post_content LIKE '%scouts-11om-magnee/%'"
	);
	wp_cache_flush();

	update_option( 'scoutsunite_reprise_s11', 1 );
}
add_action( 'init', 'scoutsunite_agenda_reprise', 20 );

function scoutsunite_agenda_title_placeholder( $text, $post ) {
	return 'reunion' === $post->post_type ? 'Réunion, hike, week-end…' : $text;
}
add_filter( 'enter_title_here', 'scoutsunite_agenda_title_placeholder', 10, 2 );

/**
 * Une réunion sans titre s'appelle « Réunion ».
 */
function scoutsunite_agenda_default_title( $data ) {
	if ( 'reunion' === $data['post_type'] && '' === trim( $data['post_title'] ) && 'auto-draft' !== $data['post_status'] ) {
		$data['post_title'] = 'Réunion';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'scoutsunite_agenda_default_title' );

/* ---------------------------------------------------------------------------
   Formulaire dans l'éditeur : le panneau « Quand et où ? »
   ------------------------------------------------------------------------ */

function scoutsunite_agenda_editor_assets() {
	$screen = get_current_screen();
	if ( ! $screen || 'reunion' !== $screen->post_type ) {
		return;
	}
	wp_enqueue_script(
		'scoutsunite-reunion',
		get_theme_file_uri( 'assets/js/reunion.js' ),
		array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-core-data' ),
		wp_get_theme()->get( 'Version' ),
		true
	);
	wp_add_inline_script(
		'scoutsunite-reunion',
		'window.scoutsuniteSections = ' . wp_json_encode( scoutsunite_agenda_sections() ) . ';',
		'before'
	);
}
add_action( 'enqueue_block_editor_assets', 'scoutsunite_agenda_editor_assets' );

/**
 * « Répéter chaque semaine jusqu'au » : crée une copie par semaine après l'enregistrement.
 * Le champ garde sa valeur ; on retient jusqu'où les copies ont été faites pour ne jamais les refaire.
 *
 * @param WP_Post $post Réunion enregistrée.
 */
function scoutsunite_agenda_repeter( $post ) {
	$jusqua = get_post_meta( $post->ID, 'su_repeter', true );
	$date   = get_post_meta( $post->ID, 'su_date', true );
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $jusqua ) || ! $date || $jusqua <= $date ) {
		return;
	}
	if ( get_post_meta( $post->ID, '_su_repete_jusqua', true ) === $jusqua || 'publish' !== $post->post_status ) {
		return;
	}
	update_post_meta( $post->ID, '_su_repete_jusqua', $jusqua );

	$meta = array();
	foreach ( array( 'su_section', 'su_debut', 'su_fin', 'su_lieu', 'su_avant' ) as $key ) {
		$meta[ $key ] = get_post_meta( $post->ID, $key, true );
	}
	$jour = new DateTimeImmutable( $date, wp_timezone() );
	$fin  = new DateTimeImmutable( $jusqua, wp_timezone() );
	for ( $i = 1; $i <= 60; $i++ ) {
		$suivant = $jour->modify( '+' . ( 7 * $i ) . ' days' );
		if ( $suivant > $fin ) {
			break;
		}
		wp_insert_post(
			array(
				'post_type'    => 'reunion',
				'post_status'  => 'publish',
				'post_title'   => $post->post_title,
				'post_content' => $post->post_content,
				'post_author'  => $post->post_author,
				'meta_input'   => array_merge( $meta, array( 'su_date' => $suivant->format( 'Y-m-d' ) ) ),
			)
		);
	}
}
add_action(
	'rest_after_insert_reunion',
	function ( $post ) {
		scoutsunite_agenda_repeter( get_post( $post->ID ) );
	}
);

/* ---------------------------------------------------------------------------
   Liste des réunions dans l'administration
   ------------------------------------------------------------------------ */

function scoutsunite_agenda_columns( $columns ) {
	$columns = array_slice( $columns, 0, 2, true ) + array(
		'su_quand'   => 'Quand',
		'su_section' => 'Section',
		'su_lieu'    => 'Lieu',
	);
	return $columns;
}
add_filter( 'manage_reunion_posts_columns', 'scoutsunite_agenda_columns' );

function scoutsunite_agenda_column( $column, $post_id ) {
	$sections = scoutsunite_agenda_sections();
	switch ( $column ) {
		case 'su_quand':
			$date = get_post_meta( $post_id, 'su_date', true );
			echo esc_html( $date ? scoutsunite_agenda_jour( $date ) . ' ' . scoutsunite_agenda_horaire( $post_id ) : '—' );
			break;
		case 'su_section':
			$s = get_post_meta( $post_id, 'su_section', true );
			echo esc_html( ( $sections[ $s ] ?? '—' ) . ( get_post_meta( $post_id, 'su_avant', true ) ? ' · à la une' : '' ) );
			break;
		case 'su_lieu':
			echo esc_html( get_post_meta( $post_id, 'su_lieu', true ) );
			break;
	}
}
add_action( 'manage_reunion_posts_custom_column', 'scoutsunite_agenda_column', 10, 2 );

function scoutsunite_agenda_sortable( $columns ) {
	$columns['su_quand'] = 'su_quand';
	return $columns;
}
add_filter( 'manage_edit-reunion_sortable_columns', 'scoutsunite_agenda_sortable' );

function scoutsunite_agenda_admin_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'reunion' !== $query->get( 'post_type' ) ) {
		return;
	}
	$orderby = $query->get( 'orderby' );
	if ( ! $orderby || 'su_quand' === $orderby ) {
		$query->set( 'meta_key', 'su_date' );
		$query->set( 'orderby', 'meta_value' );
		if ( ! $query->get( 'order' ) || ! $orderby ) {
			$query->set( 'order', 'ASC' );
		}
	}
}
add_action( 'pre_get_posts', 'scoutsunite_agenda_admin_order' );

/* ---------------------------------------------------------------------------
   Lecture des réunions
   ------------------------------------------------------------------------ */

/**
 * Réunions à venir, de la plus proche à la plus lointaine.
 *
 * @param string $section Identifiant de section, ou '' pour toutes.
 * @param int    $nombre  Nombre maximum de réunions.
 * @param bool   $avant   Seulement les activités mises en avant sur l'accueil.
 * @return WP_Post[]
 */
function scoutsunite_agenda_a_venir( $section = '', $nombre = 100, $avant = false ) {
	$meta_query = array(
		array(
			'key'     => 'su_date',
			'value'   => wp_date( 'Y-m-d' ),
			'compare' => '>=',
			'type'    => 'DATE',
		),
	);
	if ( $section ) {
		$meta_query[] = array(
			'key'   => 'su_section',
			'value' => $section,
		);
	}
	if ( $avant ) {
		$meta_query[] = array(
			'key'   => 'su_avant',
			'value' => '1',
		);
	}
	$reunions = get_posts(
		array(
			'post_type'        => 'reunion',
			'post_status'      => 'publish',
			'posts_per_page'   => 300,
			'meta_query'       => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery
			'suppress_filters' => false,
		)
	);
	// Tri par date puis par heure de début (une réunion sans heure passe en premier).
	usort(
		$reunions,
		function ( $a, $b ) {
			return strcmp(
				get_post_meta( $a->ID, 'su_date', true ) . get_post_meta( $a->ID, 'su_debut', true ),
				get_post_meta( $b->ID, 'su_date', true ) . get_post_meta( $b->ID, 'su_debut', true )
			);
		}
	);
	return array_slice( $reunions, 0, $nombre );
}

/**
 * « samedi 24 octobre » à partir de « 2026-10-24 ».
 */
function scoutsunite_agenda_jour( $date, $format = 'l j F' ) {
	$jour = DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $date . ' 12:00', wp_timezone() );
	return $jour ? wp_date( $format, $jour->getTimestamp() ) : '';
}

/**
 * « 14 h 30 » à partir de « 14:30 ».
 */
function scoutsunite_agenda_heure( $heure ) {
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', (string) $heure, $m ) ) {
		return '';
	}
	return (int) $m[1] . ' h' . ( '00' === $m[2] ? '' : ' ' . $m[2] );
}

/**
 * « 14 h – 17 h » pour une réunion.
 */
function scoutsunite_agenda_horaire( $post_id ) {
	$debut = scoutsunite_agenda_heure( get_post_meta( $post_id, 'su_debut', true ) );
	$fin   = scoutsunite_agenda_heure( get_post_meta( $post_id, 'su_fin', true ) );
	if ( $debut && $fin ) {
		return $debut . ' – ' . $fin;
	}
	return $debut ? 'dès ' . $debut : '';
}

/**
 * Adresse de la page d'une section, si elle existe.
 */
function scoutsunite_agenda_section_url( $section ) {
	$page = get_page_by_path( 'sections/' . $section );
	return $page ? get_permalink( $page ) : '';
}

/**
 * Le titre ne s'affiche que s'il dit autre chose que « Réunion ».
 */
function scoutsunite_agenda_titre( $post ) {
	$titre = get_the_title( $post );
	return 0 === strcasecmp( $titre, 'Réunion' ) ? '' : $titre;
}

/* ---------------------------------------------------------------------------
   Blocs
   ------------------------------------------------------------------------ */

function scoutsunite_agenda_blocks() {
	wp_register_script(
		'scoutsunite-blocs',
		get_theme_file_uri( 'assets/js/blocs.js' ),
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render' ),
		wp_get_theme()->get( 'Version' ),
		true
	);

	register_block_type(
		'scoutsunite/prochaines-reunions',
		array(
			'api_version'     => 3,
			'title'           => 'Prochaines réunions',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'render_callback' => 'scoutsunite_bloc_prochaines_reunions',
		)
	);
	register_block_type(
		'scoutsunite/prochaine-reunion',
		array(
			'api_version'     => 3,
			'title'           => 'Prochaine réunion d’une section',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'attributes'      => array(
				'section' => array(
					'type'    => 'string',
					'default' => 'baladins',
				),
			),
			'render_callback' => 'scoutsunite_bloc_prochaine_reunion',
		)
	);
	register_block_type(
		'scoutsunite/agenda',
		array(
			'api_version'     => 3,
			'title'           => 'Agenda complet',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'render_callback' => 'scoutsunite_bloc_agenda',
		)
	);
	register_block_type(
		'scoutsunite/reunion',
		array(
			'api_version'     => 3,
			'title'           => 'Détails de la réunion',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'uses_context'    => array( 'postId' ),
			'render_callback' => 'scoutsunite_bloc_reunion',
		)
	);
	register_block_type(
		'scoutsunite/a-la-une',
		array(
			'api_version'     => 3,
			'title'           => 'À la une',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'render_callback' => 'scoutsunite_bloc_a_la_une',
		)
	);
}
add_action( 'init', 'scoutsunite_agenda_blocks' );

/**
 * Une ligne de l'agenda, aux couleurs de la section.
 *
 * @param string $section Identifiant de la section.
 * @param string $nom     Première colonne (HTML).
 * @param string $quoi    Colonne centrale (HTML).
 * @param string $horaire Dernière colonne (texte).
 * @param string $notes   Notes sous la ligne (HTML).
 */
function scoutsunite_agenda_ligne( $section, $nom, $quoi, $horaire, $notes = '' ) {
	return sprintf(
		'<div class="wp-block-group is-style-ligne-%1$s"><p>%2$s</p><p>%3$s</p><p>%4$s</p>%5$s</div>',
		esc_attr( $section ),
		$nom,
		$quoi,
		esc_html( $horaire ),
		$notes ? '<div class="ligne__notes">' . $notes . '</div>' : ''
	);
}

/**
 * Accueil : la prochaine réunion de chaque section.
 */
function scoutsunite_bloc_prochaines_reunions() {
	$html = '';
	foreach ( scoutsunite_agenda_sections() as $slug => $nom ) {
		if ( 'unite' === $slug ) {
			continue;
		}
		$url   = scoutsunite_agenda_section_url( $slug );
		$label = $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $nom ) . '</a>' : esc_html( $nom );
		$next  = scoutsunite_agenda_a_venir( $slug, 1 );
		if ( $next ) {
			$r     = $next[0];
			$titre = scoutsunite_agenda_titre( $r );
			$quoi  = esc_html( scoutsunite_agenda_jour( get_post_meta( $r->ID, 'su_date', true ) ) );
			$lieu  = get_post_meta( $r->ID, 'su_lieu', true );
			if ( $titre ) {
				$quoi .= ' · ' . esc_html( $titre );
			}
			if ( $lieu ) {
				$quoi .= ' · ' . esc_html( $lieu );
			}
			$html .= scoutsunite_agenda_ligne( $slug, $label, $quoi, scoutsunite_agenda_horaire( $r->ID ) );
		} else {
			$html .= scoutsunite_agenda_ligne( $slug, $label, '<span class="ligne__vide">pas de réunion prévue</span>', '' );
		}
	}
	$lien = get_post_type_archive_link( 'reunion' );
	$html .= '<p class="lien-fleche" style="margin-top:var(--wp--preset--spacing--40)"><a href="' . esc_url( $lien ) . '">Tout l’agenda</a></p>';

	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'agenda-prochaines' ) ) . '>' . $html . '</div>';
}

/**
 * Page de section : la prochaine réunion, en grand.
 */
function scoutsunite_bloc_prochaine_reunion( $attributes ) {
	$section = sanitize_key( $attributes['section'] ?? 'baladins' );
	$next    = scoutsunite_agenda_a_venir( $section, 1 );
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'agenda-prochaine' ) );

	if ( ! $next ) {
		return '<div ' . $wrapper . '><h2 class="wp-block-heading affiche__titre has-x-large-font-size">Prochaine réunion</h2><p class="agenda-prochaine__date">Pas encore fixée</p><p>Le staff publiera bientôt la prochaine date dans l’agenda.</p></div>';
	}

	$r     = $next[0];
	$titre = scoutsunite_agenda_titre( $r );
	$lieu  = get_post_meta( $r->ID, 'su_lieu', true );
	$infos = array_filter( array( scoutsunite_agenda_horaire( $r->ID ), $lieu ) );

	$html  = '<h2 class="wp-block-heading affiche__titre has-x-large-font-size">Prochaine réunion</h2>';
	$html .= '<p class="agenda-prochaine__date">' . esc_html( ucfirst( scoutsunite_agenda_jour( get_post_meta( $r->ID, 'su_date', true ) ) ) ) . '</p>';
	$html .= '<p class="has-large-font-size" style="font-weight:800">' . esc_html( implode( ' · ', array_filter( array( $titre, implode( ' · ', $infos ) ) ) ) ) . '</p>';
	if ( '' !== trim( $r->post_content ) ) {
		$html .= '<div class="agenda-prochaine__notes">' . apply_filters( 'the_content', $r->post_content ) . '</div>';
	}
	$suite = scoutsunite_agenda_a_venir( $section, 4 );
	if ( count( $suite ) > 1 ) {
		$html .= '<p class="agenda-prochaine__suite"><strong>Ensuite :</strong> ';
		$dates = array();
		foreach ( array_slice( $suite, 1 ) as $s ) {
			$titre   = scoutsunite_agenda_titre( $s );
			$dates[] = esc_html( scoutsunite_agenda_jour( get_post_meta( $s->ID, 'su_date', true ), 'j F' ) . ( $titre ? ' (' . $titre . ')' : '' ) );
		}
		$html .= implode( ', ', $dates ) . '. <a href="' . esc_url( add_query_arg( 'section', $section, get_post_type_archive_link( 'reunion' ) ) ) . '">Voir l’agenda</a></p>';
	}
	return '<div ' . $wrapper . '>' . $html . '</div>';
}

/**
 * Page /agenda/ : toutes les réunions à venir, mois par mois, filtrables par section.
 */
function scoutsunite_bloc_agenda() {
	$sections = scoutsunite_agenda_sections();
	// Filtre en lecture seule depuis l'adresse, sans effet de bord.
	$filtre = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! isset( $sections[ $filtre ] ) ) {
		$filtre = '';
	}
	$base = get_post_type_archive_link( 'reunion' );

	$nav = '<nav class="agenda__filtres" aria-label="Filtrer par section"><a href="' . esc_url( $base ) . '"' . ( '' === $filtre ? ' aria-current="page"' : '' ) . '>Toutes</a>';
	foreach ( $sections as $slug => $nom ) {
		$nav .= '<a class="agenda__filtre-' . esc_attr( $slug ) . '" href="' . esc_url( add_query_arg( 'section', $slug, $base ) ) . '"' . ( $filtre === $slug ? ' aria-current="page"' : '' ) . '>' . esc_html( $nom ) . '</a>';
	}
	$nav .= '</nav>';

	$reunions = scoutsunite_agenda_a_venir( $filtre, 200 );
	if ( ! $reunions ) {
		return '<div ' . get_block_wrapper_attributes( array( 'class' => 'agenda' ) ) . '>' . $nav . '<p class="agenda__vide">Aucune réunion à venir pour l’instant. Le staff publie les dates ici dès qu’elles sont fixées.</p></div>';
	}

	$html = '';
	$mois = '';
	foreach ( $reunions as $r ) {
		$date = get_post_meta( $r->ID, 'su_date', true );
		$m    = substr( $date, 0, 7 );
		if ( $m !== $mois ) {
			$html .= ( $mois ? '</div>' : '' ) . '<h2 class="wp-block-heading agenda__mois">' . esc_html( ucfirst( scoutsunite_agenda_jour( $date, 'F Y' ) ) ) . '</h2><div class="agenda__liste">';
			$mois  = $m;
		}
		$section = get_post_meta( $r->ID, 'su_section', true );
		$titre   = scoutsunite_agenda_titre( $r );
		$lieu    = get_post_meta( $r->ID, 'su_lieu', true );
		$quoi    = '<strong>' . esc_html( scoutsunite_agenda_jour( $date ) ) . '</strong>';
		if ( $titre ) {
			$quoi .= ' · ' . esc_html( $titre );
		}
		if ( $lieu ) {
			$quoi .= ' · ' . esc_html( $lieu );
		}
		$notes = '' !== trim( $r->post_content ) ? wp_kses_post( wp_trim_words( wp_strip_all_tags( $r->post_content ), 40 ) ) : '';
		$html .= scoutsunite_agenda_ligne( isset( $sections[ $section ] ) ? $section : 'unite', esc_html( $sections[ $section ] ?? '' ), $quoi, scoutsunite_agenda_horaire( $r->ID ), $notes );
	}
	$html .= '</div>';

	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'agenda' ) ) . '>' . $nav . $html . '</div>';
}

/**
 * Page d'une réunion : quand, où, quelle section.
 */
function scoutsunite_bloc_reunion( $attributes, $content, $block ) {
	$post_id = $block->context['postId'] ?? get_the_ID();
	if ( ! $post_id || 'reunion' !== get_post_type( $post_id ) ) {
		return '';
	}
	$sections = scoutsunite_agenda_sections();
	$section  = get_post_meta( $post_id, 'su_section', true );
	$quoi     = esc_html( scoutsunite_agenda_jour( get_post_meta( $post_id, 'su_date', true ) ) );
	$lieu     = get_post_meta( $post_id, 'su_lieu', true );
	if ( $lieu ) {
		$quoi .= ' · ' . esc_html( $lieu );
	}
	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'agenda-reunion' ) ) . '>' . scoutsunite_agenda_ligne(
		isset( $sections[ $section ] ) ? $section : 'unite',
		esc_html( $sections[ $section ] ?? '' ),
		$quoi,
		scoutsunite_agenda_horaire( $post_id )
	) . '</div>';
}

/**
 * Accueil : la prochaine activité mise en avant, en grand, puis les suivantes.
 * Sans activité à venir, la dernière actualité prend la place.
 */
function scoutsunite_bloc_a_la_une() {
	$wrapper  = get_block_wrapper_attributes( array( 'class' => 'a-la-une' ) );
	$reunions = scoutsunite_agenda_a_venir( '', 4, true );

	if ( ! $reunions ) {
		$actu = get_posts( array( 'numberposts' => 1 ) );
		if ( ! $actu ) {
			return '<div ' . $wrapper . '><h2 class="wp-block-heading affiche__titre has-x-large-font-size">À la une</h2><p>Les grandes activités de l’unité s’afficheront ici.</p></div>';
		}
		$a     = $actu[0];
		$html  = '<p class="affiche__etiquette">' . esc_html( get_the_date( 'j F Y', $a ) ) . '</p>';
		$html .= '<h2 class="wp-block-heading affiche__titre has-x-large-font-size"><a href="' . esc_url( get_permalink( $a ) ) . '">' . esc_html( get_the_title( $a ) ) . '</a></h2>';
		$html .= '<p>' . esc_html( wp_trim_words( get_the_excerpt( $a ), 22 ) ) . '</p>';
		$html .= '<p class="lien-fleche"><a href="' . esc_url( get_permalink( $a ) ) . '">Lire la suite</a></p>';
		return '<div ' . $wrapper . '>' . $html . '</div>';
	}

	$sections = scoutsunite_agenda_sections();
	$r        = $reunions[0];
	$section  = get_post_meta( $r->ID, 'su_section', true );
	$nom      = $sections[ $section ] ?? '';
	$titre    = scoutsunite_agenda_titre( $r );
	if ( '' === $titre ) {
		$titre = 'unite' === $section ? 'Activité d’unité' : 'Réunion ' . $nom;
	}
	$infos = array_filter( array( scoutsunite_agenda_horaire( $r->ID ), get_post_meta( $r->ID, 'su_lieu', true ) ) );

	$html  = '<p class="affiche__etiquette">' . esc_html( $nom ) . '</p>';
	$html .= '<h2 class="wp-block-heading affiche__titre has-x-large-font-size"><a href="' . esc_url( get_permalink( $r ) ) . '">' . esc_html( $titre ) . '</a></h2>';
	$html .= '<p class="a-la-une__quand">' . esc_html( ucfirst( scoutsunite_agenda_jour( get_post_meta( $r->ID, 'su_date', true ) ) ) ) . ( $infos ? '<br>' . esc_html( implode( ' · ', $infos ) ) : '' ) . '</p>';
	if ( '' !== trim( $r->post_content ) ) {
		$html .= '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $r->post_content ), 24 ) ) . '</p>';
	}
	if ( count( $reunions ) > 1 ) {
		$suite = array();
		foreach ( array_slice( $reunions, 1 ) as $s ) {
			$t       = scoutsunite_agenda_titre( $s );
			$suite[] = '<a href="' . esc_url( get_permalink( $s ) ) . '">' . esc_html( scoutsunite_agenda_jour( get_post_meta( $s->ID, 'su_date', true ), 'j F' ) . ( $t ? ' · ' . $t : '' ) ) . '</a>';
		}
		$html .= '<p class="a-la-une__suite"><strong>Ensuite :</strong> ' . implode( ', ', $suite ) . '</p>';
	}
	$html .= '<p class="lien-fleche"><a href="' . esc_url( get_permalink( $r ) ) . '">Détails</a></p>';

	return '<div ' . $wrapper . '>' . $html . '</div>';
}
