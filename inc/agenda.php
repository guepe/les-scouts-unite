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
		'iama'       => 'IAmA',
	);
}

/**
 * Les seules sections d'âge (sans « Toute l'unité » ni le clan IAmA).
 *
 * @return array Identifiant => nom affiché.
 */
function scoutsunite_agenda_sections_age() {
	return array_diff_key( scoutsunite_agenda_sections(), array_flip( array( 'unite', 'iama' ) ) );
}

/* ---------------------------------------------------------------------------
   Type de contenu et champs
   ------------------------------------------------------------------------ */

function scoutsunite_agenda_register() {
	register_post_type(
		'reunion',
		array(
			'labels'          => array(
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
			'public'          => true,
			'has_archive'     => 'agenda',
			'rewrite'         => array(
				'slug'       => 'agenda',
				'with_front' => false,
			),
			'menu_icon'       => 'dashicons-calendar-alt',
			'menu_position'   => 5,
			'show_in_rest'    => true,
			// Droits propres aux réunions : un animateur peut gérer celles de sa section (inc/droits.php).
			'capability_type' => array( 'reunion', 'reunions' ),
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'editor', 'revisions', 'custom-fields' ),
			'template'        => array(
				array(
					'core/paragraph',
					array( 'placeholder' => 'Notes pour les parents : à emporter, rendez-vous, remarques… (facultatif)' ),
				),
			),
		)
	);

	foreach ( array( 'su_section', 'su_date', 'su_debut', 'su_fin', 'su_lieu', 'su_repeter', 'su_avant', 'su_type', 'su_jusqua', 'su_annulee', 'su_annulee_pourquoi' ) as $key ) {
		register_post_meta(
			'reunion',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'su_lieu' === $key ? 'au local' : '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'su_section' === $key ? 'scoutsunite_droits_section_permise' : 'sanitize_text_field',
				'auth_callback'     => function ( $permis, $cle, $post_id, $user_id ) {
					return user_can( $user_id, 'edit_post', $post_id );
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
		'window.scoutsuniteSections = ' . wp_json_encode( scoutsunite_droits_sections_permises() ) . ';',
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
	foreach ( array( 'su_section', 'su_debut', 'su_fin', 'su_lieu', 'su_avant', 'su_type' ) as $key ) {
		$meta[ $key ] = get_post_meta( $post->ID, $key, true );
	}
	// Un week-end ou un camp répété garde sa durée : la date de fin avance avec la copie.
	$dernier = get_post_meta( $post->ID, 'su_jusqua', true );
	$dernier = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $dernier ) && $dernier > $date ? new DateTimeImmutable( $dernier, wp_timezone() ) : null;

	$jour = new DateTimeImmutable( $date, wp_timezone() );
	$fin  = new DateTimeImmutable( $jusqua, wp_timezone() );
	for ( $i = 1; $i <= 60; $i++ ) {
		$decalage = '+' . ( 7 * $i ) . ' days';
		$suivant  = $jour->modify( $decalage );
		if ( $suivant > $fin ) {
			break;
		}
		$dates = array( 'su_date' => $suivant->format( 'Y-m-d' ) );
		if ( $dernier ) {
			$dates['su_jusqua'] = $dernier->modify( $decalage )->format( 'Y-m-d' );
		}
		wp_insert_post(
			array(
				'post_type'    => 'reunion',
				'post_status'  => 'publish',
				'post_title'   => $post->post_title,
				'post_content' => $post->post_content,
				'post_author'  => $post->post_author,
				'meta_input'   => array_merge( $meta, $dates ),
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
			echo esc_html( $date ? trim( ( scoutsunite_agenda_annulee( $post_id ) ? 'ANNULÉE – ' : '' ) . scoutsunite_agenda_type( $post_id ) . ' ' . scoutsunite_agenda_quand( $post_id ) . ' ' . scoutsunite_agenda_horaire( $post_id ) ) : '—' );
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
 * @param array  $types   Seulement ces types (« weekend », « camp ») ; vide pour tous.
 * @return WP_Post[]
 */
function scoutsunite_agenda_a_venir( $section = '', $nombre = 100, $avant = false, $types = array() ) {
	$aujourdhui = wp_date( 'Y-m-d' );
	// Un week-end ou un camp reste « à venir » jusqu'à son dernier jour.
	$meta_query = array(
		array(
			'relation' => 'OR',
			array(
				'key'     => 'su_date',
				'value'   => $aujourdhui,
				'compare' => '>=',
				'type'    => 'DATE',
			),
			array(
				'key'     => 'su_jusqua',
				'value'   => $aujourdhui,
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);
	if ( $section ) {
		$meta_query[] = array(
			'key'     => 'su_section',
			'value'   => $section,
			'compare' => is_array( $section ) ? 'IN' : '=',
		);
	}
	if ( $types ) {
		$meta_query[] = array(
			'key'     => 'su_type',
			'value'   => $types,
			'compare' => 'IN',
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
 * Date d'une activité : un jour, ou une période pour un week-end ou un camp.
 * Format long : « du vendredi 13 au dimanche 15 novembre » ; court (« j F ») : « 13 – 15 novembre ».
 *
 * @param int    $post_id Réunion.
 * @param string $format  'l j F' (long) ou 'j F' (court).
 * @return string
 */
function scoutsunite_agenda_quand( $post_id, $format = 'l j F' ) {
	$debut = get_post_meta( $post_id, 'su_date', true );
	$fin   = get_post_meta( $post_id, 'su_jusqua', true );
	if ( ! $fin || $fin <= $debut || ! get_post_meta( $post_id, 'su_type', true ) ) {
		return scoutsunite_agenda_jour( $debut, $format );
	}
	$meme_mois = substr( $debut, 0, 7 ) === substr( $fin, 0, 7 );
	if ( 'j F' === $format ) {
		return scoutsunite_agenda_jour( $debut, $meme_mois ? 'j' : 'j F' ) . ' – ' . scoutsunite_agenda_jour( $fin, 'j F' );
	}
	return 'du ' . scoutsunite_agenda_jour( $debut, $meme_mois ? 'l j' : 'l j F' ) . ' au ' . scoutsunite_agenda_jour( $fin, 'l j F' );
}

/**
 * « Camp », « Week-end » ou '' pour une réunion ordinaire.
 *
 * @param int $post_id Réunion.
 * @return string
 */
function scoutsunite_agenda_type( $post_id ) {
	$types = array(
		'weekend'  => 'Week-end',
		'camp'     => 'Camp',
		'speciale' => 'Réunion spéciale',
	);
	return $types[ get_post_meta( $post_id, 'su_type', true ) ] ?? '';
}

/**
 * Étiquette de type à placer devant une ligne d'agenda.
 *
 * @param int $post_id Réunion.
 * @return string HTML.
 */
function scoutsunite_agenda_etiquette( $post_id ) {
	$type = scoutsunite_agenda_type( $post_id );
	$html = scoutsunite_agenda_annulee( $post_id ) ? '<span class="ligne__type ligne__type--annulee">Annulée</span> ' : '';
	return $html . ( $type ? '<span class="ligne__type">' . esc_html( $type ) . '</span> ' : '' );
}

/**
 * La réunion est-elle annulée ? Elle reste affichée jusqu'à sa date, marquée « Annulée ».
 *
 * @param int $post_id Réunion.
 * @return bool
 */
function scoutsunite_agenda_annulee( $post_id ) {
	return '1' === get_post_meta( $post_id, 'su_annulee', true );
}

/**
 * Le mot d'explication d'une annulation, '' s'il n'y en a pas.
 *
 * @param int $post_id Réunion.
 * @return string Texte brut.
 */
function scoutsunite_agenda_pourquoi( $post_id ) {
	return scoutsunite_agenda_annulee( $post_id ) ? trim( (string) get_post_meta( $post_id, 'su_annulee_pourquoi', true ) ) : '';
}

/**
 * Répartit les réunions à venir : la première maintenue, et les annulées qui la précèdent.
 *
 * @param WP_Post[] $reunions Réunions à venir, dans l'ordre.
 * @return array [ WP_Post|null prochaine maintenue, WP_Post[] annulées avant elle, WP_Post[] suivantes ].
 */
function scoutsunite_agenda_prochaine_maintenue( $reunions ) {
	$annulees = array();
	foreach ( $reunions as $i => $r ) {
		if ( ! scoutsunite_agenda_annulee( $r->ID ) ) {
			return array( $r, $annulees, array_slice( $reunions, $i + 1 ) );
		}
		$annulees[] = $r;
	}
	return array( null, $annulees, array() );
}

/**
 * « Samedi 7 novembre : réunion annulée (local indisponible). », une ligne par réunion annulée.
 *
 * @param WP_Post[] $annulees Réunions annulées.
 * @param bool      $date     Rappeler la date (inutile quand elle est affichée juste en dessous).
 * @return string HTML.
 */
function scoutsunite_agenda_rappel_annulees( $annulees, $date = true ) {
	$html = '';
	foreach ( $annulees as $a ) {
		$titre    = scoutsunite_agenda_titre( $a );
		$pourquoi = scoutsunite_agenda_pourquoi( $a->ID );
		$texte    = $date ? ucfirst( scoutsunite_agenda_quand( $a->ID ) ) . ( $titre ? ' (' . $titre . ')' : '' ) . ( $pourquoi ? ' : ' . $pourquoi : '' ) : $pourquoi;
		$html    .= '<p class="annulee"><span class="ligne__type ligne__type--annulee">Annulée</span> ' . esc_html( $texte ) . '</p>';
	}
	return $html;
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
	$page = 'iama' === $section ? scoutsunite_page_iama() : get_page_by_path( 'sections/' . $section );
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
		'scoutsunite/camps',
		array(
			'api_version'     => 3,
			'title'           => 'Temps forts de la section',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'attributes'      => array(
				'section' => array(
					'type'    => 'string',
					'default' => 'baladins',
				),
			),
			'render_callback' => 'scoutsunite_bloc_camps',
		)
	);
	register_block_type(
		'scoutsunite/iama',
		array(
			'api_version'     => 3,
			'title'           => 'Activités IAmA',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'render_callback' => 'scoutsunite_bloc_iama',
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
 * @param bool   $annulee Réunion annulée : la ligne est barrée.
 */
function scoutsunite_agenda_ligne( $section, $nom, $quoi, $horaire, $notes = '', $annulee = false ) {
	return sprintf(
		'<div class="wp-block-group is-style-ligne-%1$s%6$s"><p>%2$s</p><p>%3$s</p><p>%4$s</p>%5$s</div>',
		esc_attr( $section ),
		$nom,
		$quoi,
		esc_html( $horaire ),
		$notes ? '<div class="ligne__notes">' . $notes . '</div>' : '',
		$annulee ? ' ligne--annulee' : ''
	);
}

/**
 * Accueil : la prochaine réunion de chaque section.
 */
function scoutsunite_bloc_prochaines_reunions() {
	$html = '';
	foreach ( scoutsunite_agenda_sections_age() as $slug => $nom ) {
		$url   = scoutsunite_agenda_section_url( $slug );
		$label = $url ? '<a href="' . esc_url( $url ) . '">' . esc_html( $nom ) . '</a>' : esc_html( $nom );
		// Une réunion annulée cède la place à la suivante, avec un mot pour la signaler.
		list( $r, $annulees ) = scoutsunite_agenda_prochaine_maintenue( scoutsunite_agenda_a_venir( $slug, 6 ) );
		$avant                = '';
		foreach ( $annulees as $a ) {
			$avant .= '<span class="ligne__type ligne__type--annulee">Annulée</span> <s>' . esc_html( scoutsunite_agenda_quand( $a->ID, 'j F' ) ) . '</s> · ';
		}
		if ( $r ) {
			$titre = scoutsunite_agenda_titre( $r );
			$quoi  = $avant . scoutsunite_agenda_etiquette( $r->ID ) . esc_html( scoutsunite_agenda_quand( $r->ID ) );
			$lieu  = get_post_meta( $r->ID, 'su_lieu', true );
			if ( $titre ) {
				$quoi .= ' · ' . esc_html( $titre );
			}
			if ( $lieu ) {
				$quoi .= ' · ' . esc_html( $lieu );
			}
			$html .= scoutsunite_agenda_ligne( $slug, $label, $quoi, scoutsunite_agenda_horaire( $r->ID ) );
		} else {
			$html .= scoutsunite_agenda_ligne( $slug, $label, $avant . '<span class="ligne__vide">pas de réunion prévue</span>', '' );
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
	$wrapper = get_block_wrapper_attributes( array( 'class' => 'agenda-prochaine' ) );
	// Une réunion annulée cède la place à la suivante ; un rappel la signale au-dessus.
	list( $r, $annulees, $suite ) = scoutsunite_agenda_prochaine_maintenue( scoutsunite_agenda_a_venir( $section, 8 ) );
	$rappel                       = $annulees ? '<div class="agenda-prochaine__annulees">' . scoutsunite_agenda_rappel_annulees( $annulees ) . '</div>' : '';

	if ( ! $r ) {
		return '<div ' . $wrapper . '><h2 class="wp-block-heading affiche__titre has-x-large-font-size">Prochaine réunion</h2>' . $rappel . '<p class="agenda-prochaine__date">Pas encore fixée</p><p>Le staff publiera bientôt la prochaine date dans l’agenda.</p>' . scoutsunite_agenda_bon_a_savoir( $section ) . scoutsunite_liens_pratiques( $section ) . '</div>';
	}

	$titre = scoutsunite_agenda_titre( $r );
	$titre = $titre ? $titre : scoutsunite_agenda_type( $r->ID );
	$lieu  = get_post_meta( $r->ID, 'su_lieu', true );
	$infos = array_filter( array( scoutsunite_agenda_horaire( $r->ID ), $lieu ) );

	$html  = '<h2 class="wp-block-heading affiche__titre has-x-large-font-size">Prochaine réunion</h2>' . $rappel;
	$html .= '<p class="agenda-prochaine__date">' . esc_html( ucfirst( scoutsunite_agenda_quand( $r->ID ) ) ) . '</p>';
	$html .= '<p class="has-large-font-size" style="font-weight:800">' . esc_html( implode( ' · ', array_filter( array( $titre, implode( ' · ', $infos ) ) ) ) ) . '</p>';
	if ( '' !== trim( $r->post_content ) ) {
		$html .= '<div class="agenda-prochaine__notes">' . apply_filters( 'the_content', $r->post_content ) . '</div>';
	}
	$suite = array_slice( $suite, 0, 3 );
	if ( $suite ) {
		$html .= '<p class="agenda-prochaine__suite"><strong>Ensuite :</strong> ';
		$dates = array();
		foreach ( $suite as $s ) {
			$titre   = scoutsunite_agenda_titre( $s );
			$titre   = $titre ? $titre : scoutsunite_agenda_type( $s->ID );
			$quand   = esc_html( scoutsunite_agenda_quand( $s->ID, 'j F' ) . ( $titre ? ' (' . $titre . ')' : '' ) );
			$dates[] = scoutsunite_agenda_annulee( $s->ID ) ? '<s>' . $quand . '</s> (annulée)' : $quand;
		}
		$html .= implode( ', ', $dates ) . '. <a href="' . esc_url( add_query_arg( 'section', $section, get_post_type_archive_link( 'reunion' ) ) ) . '">Voir l’agenda</a></p>';
	}
	$html .= scoutsunite_agenda_bon_a_savoir( $section );
	$html .= scoutsunite_liens_pratiques( $section );
	return '<div ' . $wrapper . '>' . $html . '</div>';
}

/**
 * Encadré « Bon à savoir » sous la prochaine réunion d'une section.
 *
 * @param string $section Identifiant de section.
 * @return string HTML.
 */
function scoutsunite_agenda_bon_a_savoir( $section ) {
	$liste = scoutsunite_bon_a_savoir( $section );
	return $liste ? '<div class="bon-a-savoir"><h3 class="bon-a-savoir__titre">Bon à savoir</h3>' . $liste . '</div>' : '';
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
		return '<div ' . get_block_wrapper_attributes( array( 'class' => 'agenda' ) ) . '>' . $nav . '<p class="agenda__vide">Aucune réunion à venir pour l’instant. Le staff publie les dates ici dès qu’elles sont fixées.</p>' . scoutsunite_liens_pratiques( $filtre ) . '</div>';
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
		$quoi    = scoutsunite_agenda_etiquette( $r->ID ) . '<strong>' . esc_html( scoutsunite_agenda_quand( $r->ID ) ) . '</strong>';
		if ( $titre ) {
			$quoi .= ' · ' . esc_html( $titre );
		}
		if ( $lieu ) {
			$quoi .= ' · ' . esc_html( $lieu );
		}
		$notes    = '' !== trim( $r->post_content ) ? wp_kses_post( wp_trim_words( wp_strip_all_tags( $r->post_content ), 40 ) ) : '';
		// Une réunion annulée ne garde que le mot d'explication : ses notes ne servent plus.
		if ( scoutsunite_agenda_annulee( $r->ID ) ) {
			$pourquoi = scoutsunite_agenda_pourquoi( $r->ID );
			$notes    = $pourquoi ? '<strong>' . esc_html( $pourquoi ) . '</strong>' : '';
		}
		$html .= scoutsunite_agenda_ligne( isset( $sections[ $section ] ) ? $section : 'unite', esc_html( $sections[ $section ] ?? '' ), $quoi, scoutsunite_agenda_horaire( $r->ID ), $notes, scoutsunite_agenda_annulee( $r->ID ) );
	}
	$html .= '</div>';
	$html .= scoutsunite_liens_pratiques( $filtre );

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
	$quoi     = scoutsunite_agenda_etiquette( $post_id ) . esc_html( scoutsunite_agenda_quand( $post_id ) );
	$lieu     = get_post_meta( $post_id, 'su_lieu', true );
	if ( $lieu ) {
		$quoi .= ' · ' . esc_html( $lieu );
	}
	$pourquoi = scoutsunite_agenda_pourquoi( $post_id );
	return '<div ' . get_block_wrapper_attributes( array( 'class' => 'agenda-reunion' ) ) . '>' . scoutsunite_agenda_ligne(
		isset( $sections[ $section ] ) ? $section : 'unite',
		esc_html( $sections[ $section ] ?? '' ),
		$quoi,
		scoutsunite_agenda_horaire( $post_id ),
		$pourquoi ? '<strong>' . esc_html( $pourquoi ) . '</strong>' : '',
		scoutsunite_agenda_annulee( $post_id )
	) . scoutsunite_liens_pratiques( '', $post_id ) . '</div>';
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
		$titre = scoutsunite_agenda_type( $r->ID );
	}
	if ( '' === $titre ) {
		$titre = in_array( $section, array( 'unite', 'iama' ), true ) ? ( 'unite' === $section ? 'Activité d’unité' : 'Activité IAmA' ) : 'Réunion ' . $nom;
	}
	$infos = array_filter( array( scoutsunite_agenda_horaire( $r->ID ), get_post_meta( $r->ID, 'su_lieu', true ) ) );

	$html  = '<p class="affiche__etiquette">' . esc_html( implode( ' · ', array_filter( array( $nom, scoutsunite_agenda_type( $r->ID ) ) ) ) ) . '</p>';
	if ( scoutsunite_agenda_annulee( $r->ID ) ) {
		$html .= scoutsunite_agenda_rappel_annulees( array( $r ), false );
	}
	$html .= '<h2 class="wp-block-heading affiche__titre has-x-large-font-size"><a href="' . esc_url( get_permalink( $r ) ) . '">' . esc_html( $titre ) . '</a></h2>';
	$html .= '<p class="a-la-une__quand">' . esc_html( ucfirst( scoutsunite_agenda_quand( $r->ID ) ) ) . ( $infos ? '<br>' . esc_html( implode( ' · ', $infos ) ) : '' ) . '</p>';
	if ( '' !== trim( $r->post_content ) ) {
		$html .= '<p>' . esc_html( wp_trim_words( wp_strip_all_tags( $r->post_content ), 24 ) ) . '</p>';
	}
	if ( count( $reunions ) > 1 ) {
		$suite = array();
		foreach ( array_slice( $reunions, 1 ) as $s ) {
			$t       = scoutsunite_agenda_titre( $s );
			$suite[] = '<a href="' . esc_url( get_permalink( $s ) ) . '">' . esc_html( scoutsunite_agenda_quand( $s->ID, 'j F' ) . ( $t ? ' · ' . $t : '' ) ) . '</a>';
		}
		$html .= '<p class="a-la-une__suite"><strong>Ensuite :</strong> ' . implode( ', ', $suite ) . '</p>';
	}

	return '<div ' . $wrapper . '>' . $html . '</div>';
}

/**
 * Fiches d'activités (week-ends, camps, activités IAmA) avec leurs notes complètes.
 *
 * @param WP_Post[] $activites Réunions à afficher.
 * @return string HTML.
 */
function scoutsunite_agenda_fiches( $activites ) {
	$html = '';
	foreach ( $activites as $a ) {
		$section   = get_post_meta( $a->ID, 'su_section', true );
		$type      = scoutsunite_agenda_type( $a->ID );
		$titre     = scoutsunite_agenda_titre( $a );
		$infos     = array_filter( array( scoutsunite_agenda_horaire( $a->ID ), get_post_meta( $a->ID, 'su_lieu', true ) ) );
		$qui       = array( 'unite' => 'toute l’unité', 'iama' => 'IAmA' );
		$etiquette = implode( ' · ', array_filter( array( $type ? $type : ( isset( $qui[ $section ] ) ? 'Activité' : '' ), $qui[ $section ] ?? '' ) ) );

		$html .= '<article class="camp">';
		$html .= '<p class="camp__type">' . esc_html( $etiquette ) . '</p>';
		if ( scoutsunite_agenda_annulee( $a->ID ) ) {
			$html .= scoutsunite_agenda_rappel_annulees( array( $a ), false );
		}
		$html .= '<h4 class="wp-block-heading camp__titre"><a href="' . esc_url( get_permalink( $a ) ) . '">' . esc_html( $titre ? $titre : ( $type ? $type : 'Activité' ) ) . '</a></h4>';
		$html .= '<p class="camp__quand">' . esc_html( ucfirst( scoutsunite_agenda_quand( $a->ID ) ) ) . ( $infos ? '<br>' . esc_html( implode( ' · ', $infos ) ) : '' ) . '</p>';
		if ( '' !== trim( $a->post_content ) ) {
			$html .= '<div class="camp__notes">' . apply_filters( 'the_content', $a->post_content ) . '</div>';
		}
		$html .= '</article>';
	}
	return $html;
}

/**
 * Page de section : les temps forts à venir (week-ends, camp, réunions spéciales)
 * de la section et de toute l'unité, avec leurs notes complètes (prix, matériel…).
 *
 * @param array $attributes Attributs du bloc (section).
 * @return string HTML.
 */
function scoutsunite_bloc_camps( $attributes ) {
	$section   = sanitize_key( $attributes['section'] ?? 'baladins' );
	$wrapper   = get_block_wrapper_attributes( array( 'class' => 'camps' ) );
	$activites = scoutsunite_agenda_a_venir( array( $section, 'unite' ), 6, false, array( 'weekend', 'camp', 'speciale' ) );

	if ( ! $activites ) {
		return '<div ' . $wrapper . '><p class="camps__vide">Pas encore de temps fort annoncé. Dès que le staff ajoute à l’agenda un week-end, le camp ou une réunion spéciale, ils s’affichent ici avec les dates, le lieu et les infos pratiques.</p></div>';
	}
	return '<div ' . $wrapper . '>' . scoutsunite_agenda_fiches( $activites ) . '</div>';
}

/**
 * Page IAmA : les prochaines activités du clan des anciens et amis.
 *
 * @return string HTML.
 */
function scoutsunite_bloc_iama() {
	$wrapper   = get_block_wrapper_attributes( array( 'class' => 'camps' ) );
	$activites = scoutsunite_agenda_a_venir( 'iama', 6 );

	if ( ! $activites ) {
		return '<div ' . $wrapper . '><p class="camps__vide">Pas encore d’activité IAmA annoncée. Les rendez-vous du clan (souper des anciens, coup de main au local…) s’affichent ici dès qu’ils sont ajoutés à l’agenda avec la section « IAmA ».</p></div>';
	}
	return '<div ' . $wrapper . '>' . scoutsunite_agenda_fiches( $activites ) . '</div>';
}
