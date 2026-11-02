<?php
/**
 * Documents importants : les fichiers que les parents téléchargent (fiche médicale,
 * autorisations, liste du matériel, règlement…).
 *
 * Le staff les ajoute dans Documents → Ajouter : un titre, un fichier de la médiathèque,
 * la section concernée (ou toute l'unité) et une ligne d'explication. Le bloc
 * « Documents » les affiche sur la page de section et sur la page Documents.
 * Un animateur ne gère que les documents de ses sections (inc/droits.php).
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function scoutsunite_documents_register() {
	register_post_type(
		'document',
		array(
			'labels'          => array(
				'name'               => 'Documents',
				'singular_name'      => 'Document',
				'menu_name'          => 'Documents',
				'all_items'          => 'Tous les documents',
				'add_new'            => 'Ajouter',
				'add_new_item'       => 'Ajouter un document',
				'edit_item'          => 'Modifier le document',
				'new_item'           => 'Nouveau document',
				'search_items'       => 'Rechercher un document',
				'not_found'          => 'Aucun document',
				'not_found_in_trash' => 'Aucun document dans la corbeille',
			),
			// Pas de page par document : le lien mène directement au fichier.
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => true,
			'menu_icon'       => 'dashicons-media-document',
			'menu_position'   => 6,
			'capability_type' => array( 'document', 'documents' ),
			'map_meta_cap'    => true,
			'supports'        => array( 'title', 'page-attributes' ),
		)
	);

	foreach ( array( 'su_section', 'su_fichier', 'su_explication' ) as $key ) {
		register_post_meta(
			'document',
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'su_section' === $key ? 'scoutsunite_droits_section_permise' : ( 'su_fichier' === $key ? 'scoutsunite_documents_fichier_permis' : 'sanitize_text_field' ),
				'auth_callback'     => function ( $permis, $cle, $post_id, $user_id ) {
					return user_can( $user_id, 'edit_post', $post_id );
				},
			)
		);
	}
}
add_action( 'init', 'scoutsunite_documents_register' );

/**
 * Le fichier d'un document : un fichier de la médiathèque que la personne peut voir.
 * Sinon, un document publié pourrait montrer le fichier joint d'un brouillon ou d'une page privée.
 *
 * @param mixed $valeur Identifiant du fichier reçu.
 * @return string Identifiant retenu, '' s'il n'est pas permis.
 */
function scoutsunite_documents_fichier_permis( $valeur ) {
	$fichier = absint( $valeur );
	if ( ! $fichier || 'attachment' !== get_post_type( $fichier ) ) {
		return '';
	}
	// Sans personne connectée (WP-CLI, tâche planifiée), seul le type est vérifié.
	return ! is_user_logged_in() || current_user_can( 'read_post', $fichier ) ? (string) $fichier : '';
}

function scoutsunite_documents_title_placeholder( $text, $post ) {
	return 'document' === $post->post_type ? 'Fiche médicale, autorisation parentale, liste du matériel…' : $text;
}
add_filter( 'enter_title_here', 'scoutsunite_documents_title_placeholder', 10, 2 );

/* ---------------------------------------------------------------------------
   Formulaire : section, fichier, explication
   ------------------------------------------------------------------------ */

function scoutsunite_documents_boite() {
	add_meta_box( 'scoutsunite-document', 'Le document', 'scoutsunite_documents_formulaire', 'document', 'normal', 'high' );
}
add_action( 'add_meta_boxes_document', 'scoutsunite_documents_boite' );

/**
 * Champs du document, sous son titre.
 *
 * @param WP_Post $post Document.
 */
function scoutsunite_documents_formulaire( $post ) {
	wp_nonce_field( 'scoutsunite_document', 'scoutsunite_document_nonce' );
	$section = get_post_meta( $post->ID, 'su_section', true );
	$fichier = (int) get_post_meta( $post->ID, 'su_fichier', true );
	$nom     = $fichier ? basename( (string) get_attached_file( $fichier ) ) : '';
	?>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="su-doc-section">Pour qui ?</label></th>
			<td>
				<select id="su-doc-section" name="su_section">
					<?php foreach ( scoutsunite_droits_sections_permises() as $slug => $libelle ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $section, $slug ); ?>><?php echo esc_html( $libelle ); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description">Un document de toute l’unité s’affiche sur chaque page de section.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Fichier</th>
			<td>
				<input type="hidden" id="su-doc-fichier" name="su_fichier" value="<?php echo esc_attr( $fichier ? $fichier : '' ); ?>">
				<p id="su-doc-nom"><?php echo $nom ? '<strong>' . esc_html( $nom ) . '</strong>' : 'Aucun fichier choisi.'; ?></p>
				<button type="button" class="button" id="su-doc-choisir">Choisir ou téléverser le fichier</button>
				<p class="description">Un PDF de préférence : il s’ouvre sur tous les téléphones. Un document Word ou une image conviennent aussi.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="su-doc-explication">Explication</label></th>
			<td>
				<input class="large-text" type="text" id="su-doc-explication" name="su_explication" value="<?php echo esc_attr( get_post_meta( $post->ID, 'su_explication', true ) ); ?>" placeholder="À remplir et à rendre au staff avant le camp">
				<p class="description">Facultatif : une ligne sous le titre.</p>
			</td>
		</tr>
	</table>
	<script>
	( function () {
		var cadre;
		document.getElementById( 'su-doc-choisir' ).addEventListener( 'click', function () {
			if ( ! cadre ) {
				cadre = wp.media( { title: 'Choisir le document', button: { text: 'Utiliser ce fichier' }, multiple: false } );
				cadre.on( 'select', function () {
					var fichier = cadre.state().get( 'selection' ).first().toJSON();
					document.getElementById( 'su-doc-fichier' ).value = fichier.id;
					document.getElementById( 'su-doc-nom' ).innerHTML = '<strong></strong>';
					document.querySelector( '#su-doc-nom strong' ).textContent = fichier.filename;
					var titre = document.getElementById( 'title' );
					if ( titre && ! titre.value ) {
						titre.value = fichier.title;
						titre.dispatchEvent( new Event( 'input' ) );
					}
				} );
			}
			cadre.open();
		} );
	} )();
	</script>
	<?php
}

/**
 * La médiathèque n'est chargée que sur l'écran d'un document.
 */
function scoutsunite_documents_media() {
	$ecran = get_current_screen();
	if ( $ecran && 'document' === $ecran->post_type && 'post' === $ecran->base ) {
		wp_enqueue_media();
	}
}
add_action( 'admin_enqueue_scripts', 'scoutsunite_documents_media' );

/**
 * Enregistrement des champs du formulaire.
 *
 * @param int $post_id Document.
 */
function scoutsunite_documents_enregistrer( $post_id ) {
	if ( ! isset( $_POST['scoutsunite_document_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['scoutsunite_document_nonce'] ) ), 'scoutsunite_document' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( isset( $_POST['su_section'] ) ) {
		update_post_meta( $post_id, 'su_section', scoutsunite_droits_section_permise( wp_unslash( $_POST['su_section'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- nettoyé par scoutsunite_droits_section_permise().
	}
	if ( isset( $_POST['su_fichier'] ) ) {
		update_post_meta( $post_id, 'su_fichier', scoutsunite_documents_fichier_permis( absint( $_POST['su_fichier'] ) ) );
	}
	if ( isset( $_POST['su_explication'] ) ) {
		update_post_meta( $post_id, 'su_explication', sanitize_text_field( wp_unslash( $_POST['su_explication'] ) ) );
	}
}
add_action( 'save_post_document', 'scoutsunite_documents_enregistrer' );

/**
 * Un document publié sans fichier n'apparaît nulle part : on le dit.
 */
function scoutsunite_documents_avis() {
	$ecran = get_current_screen();
	$post  = get_post();
	if ( $ecran && 'document' === $ecran->post_type && 'post' === $ecran->base && $post && 'publish' === $post->post_status && ! scoutsunite_documents_url( $post->ID ) ) {
		echo '<div class="notice notice-warning"><p>Ce document n’a pas encore de fichier : il ne s’affiche pas sur le site.</p></div>';
	}
}
add_action( 'admin_notices', 'scoutsunite_documents_avis' );

/* ---------------------------------------------------------------------------
   Liste dans l'administration
   ------------------------------------------------------------------------ */

function scoutsunite_documents_colonnes( $columns ) {
	return array_slice( $columns, 0, 2, true ) + array(
		'su_section' => 'Pour qui ?',
		'su_fichier' => 'Fichier',
	);
}
add_filter( 'manage_document_posts_columns', 'scoutsunite_documents_colonnes' );

function scoutsunite_documents_colonne( $column, $post_id ) {
	if ( 'su_section' === $column ) {
		echo esc_html( scoutsunite_agenda_sections()[ get_post_meta( $post_id, 'su_section', true ) ] ?? '—' );
	} elseif ( 'su_fichier' === $column ) {
		$fichier = (int) get_post_meta( $post_id, 'su_fichier', true );
		echo $fichier ? esc_html( basename( (string) get_attached_file( $fichier ) ) ) : '<em>aucun fichier</em>';
	}
}
add_action( 'manage_document_posts_custom_column', 'scoutsunite_documents_colonne', 10, 2 );

/* ---------------------------------------------------------------------------
   Lecture et affichage
   ------------------------------------------------------------------------ */

/**
 * Adresse du fichier d'un document, '' s'il n'en a pas.
 *
 * @param int $post_id Document.
 * @return string URL.
 */
function scoutsunite_documents_url( $post_id ) {
	$fichier = (int) get_post_meta( $post_id, 'su_fichier', true );
	return $fichier ? (string) wp_get_attachment_url( $fichier ) : '';
}

/**
 * Documents publiés de ces sections, dans l'ordre choisi (« Ordre ») puis par titre.
 *
 * @param string[] $sections Identifiants de section.
 * @return WP_Post[]
 */
function scoutsunite_documents( $sections ) {
	return array_values(
		array_filter(
			get_posts(
				array(
					'post_type'      => 'document',
					'post_status'    => 'publish',
					'posts_per_page' => 100,
					'orderby'        => array(
						'menu_order' => 'ASC',
						'title'      => 'ASC',
					),
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
						array(
							'key'     => 'su_section',
							'value'   => $sections,
							'compare' => 'IN',
						),
					),
				)
			),
			function ( $d ) {
				return '' !== scoutsunite_documents_url( $d->ID );
			}
		)
	);
}

/**
 * Une ligne par document : le type de fichier, le titre en lien, l'explication et la taille.
 *
 * @param WP_Post[] $documents Documents.
 * @param string    $section   Section affichée : un document de toute l'unité reçoit une étiquette.
 * @return string HTML.
 */
function scoutsunite_documents_liste( $documents, $section = '' ) {
	$html = '<ul class="documents__liste">';
	foreach ( $documents as $d ) {
		$fichier = (int) get_post_meta( $d->ID, 'su_fichier', true );
		$chemin  = (string) get_attached_file( $fichier );
		$type    = strtoupper( (string) pathinfo( $chemin, PATHINFO_EXTENSION ) );
		$taille  = is_readable( $chemin ) ? size_format( filesize( $chemin ), 0 ) : '';
		$expl    = get_post_meta( $d->ID, 'su_explication', true );
		$unite   = $section && 'unite' !== $section && 'unite' === get_post_meta( $d->ID, 'su_section', true );

		$html .= '<li class="documents__item">';
		$html .= '<span class="documents__type" aria-hidden="true">' . esc_html( $type ? $type : 'DOC' ) . '</span>';
		$html .= '<span class="documents__texte"><a class="documents__lien" href="' . esc_url( scoutsunite_documents_url( $d->ID ) ) . '">' . esc_html( get_the_title( $d ) ) . '</a>';
		if ( $unite ) {
			$html .= ' <span class="documents__unite">toute l’unité</span>';
		}
		if ( $expl ) {
			$html .= '<span class="documents__explication">' . esc_html( $expl ) . '</span>';
		}
		$html .= '<span class="documents__infos">' . esc_html( implode( ' · ', array_filter( array( $type, $taille ) ) ) ) . '</span></span></li>';
	}
	return $html . '</ul>';
}

/**
 * Bloc « Documents » : ceux d'une section (avec ceux de toute l'unité), ou tous, rangés par section.
 *
 * @param array $attributes Attributs du bloc (section, '' pour tous).
 * @return string HTML.
 */
function scoutsunite_bloc_documents( $attributes ) {
	$sections = scoutsunite_agenda_sections();
	$section  = sanitize_key( $attributes['section'] ?? '' );
	$wrapper  = get_block_wrapper_attributes( array( 'class' => 'documents' ) );
	$vide     = '<p class="documents__vide">Pas encore de document à télécharger. Le staff les ajoute ici dès qu’ils sont prêts (fiche médicale, autorisations, liste du matériel…).</p>';

	if ( isset( $sections[ $section ] ) ) {
		$documents = scoutsunite_documents( array_unique( array( 'unite', $section ) ) );
		return '<div ' . $wrapper . '>' . ( $documents ? scoutsunite_documents_liste( $documents, $section ) : $vide ) . '</div>';
	}

	// Page Documents : toute l'unité d'abord, puis chaque section dans l'ordre des âges.
	$html = '';
	foreach ( array_merge( array( 'unite' => $sections['unite'] ), array_diff_key( $sections, array( 'unite' => '' ) ) ) as $slug => $nom ) {
		$documents = scoutsunite_documents( array( $slug ) );
		if ( $documents ) {
			$html .= '<h2 class="wp-block-heading documents__section documents--' . esc_attr( $slug ) . '">' . esc_html( $nom ) . '</h2>' . scoutsunite_documents_liste( $documents );
		}
	}
	return '<div ' . $wrapper . '>' . ( $html ? $html : $vide ) . '</div>';
}

function scoutsunite_documents_bloc() {
	register_block_type(
		'scoutsunite/documents',
		array(
			'api_version'     => 3,
			'title'           => 'Documents',
			'category'        => 'widgets',
			'editor_script'   => 'scoutsunite-blocs',
			'attributes'      => array(
				'section' => array(
					'type'    => 'string',
					'default' => '',
				),
			),
			'render_callback' => 'scoutsunite_bloc_documents',
		)
	);
}
add_action( 'init', 'scoutsunite_documents_bloc' );

/**
 * Reprise des pages de section déjà créées : sous « Les temps forts », un titre
 * « Les documents » et le bloc « Documents » de la section. Une seule fois, et
 * jamais sur une page qui a déjà le bloc.
 */
function scoutsunite_reprise_documents() {
	if ( get_option( 'scoutsunite_reprise_documents' ) ) {
		return;
	}
	global $wpdb;

	$pages = $wpdb->get_results( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'page' AND post_content LIKE '%wp:scoutsunite/camps {%' AND post_content NOT LIKE '%wp:scoutsunite/documents%'" );
	foreach ( $pages as $page ) {
		$contenu = preg_replace(
			'#<!-- wp:scoutsunite/camps \{"section":"([a-z]+)"\} /-->#',
			"$0\n\n<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">Les documents</h3>\n<!-- /wp:heading -->\n\n<!-- wp:scoutsunite/documents {\"section\":\"$1\"} /-->",
			$page->post_content,
			1
		);
		if ( $contenu !== $page->post_content ) {
			$wpdb->update( $wpdb->posts, array( 'post_content' => $contenu ), array( 'ID' => $page->ID ) );
			clean_post_cache( $page->ID );
		}
	}

	update_option( 'scoutsunite_reprise_documents', 1 );
}
add_action( 'init', 'scoutsunite_reprise_documents', 20 );
