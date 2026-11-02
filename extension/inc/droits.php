<?php
/**
 * Droits d'accès : les rôles « Animateur » et « Staff d'unité ».
 *
 * Un animateur est rattaché à une ou plusieurs sections (dans son profil, par un
 * administrateur). Il gère les réunions et les documents de ses sections et modifie les textes et
 * images de leur page, rien d'autre. Le staff d'unité gère tout le contenu et
 * Réglages → Unité, sans les réglages techniques du site.
 *
 * Les limites sont vérifiées par WordPress lui-même (map_meta_cap), pas seulement
 * cachées dans l'interface.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Version des rôles : l'augmenter pour réécrire les rôles au prochain chargement. */
const SCOUTSUNITE_DROITS_VERSION = 4;

/**
 * Droits sur les réunions (type de contenu « reunion »).
 *
 * @return array
 */
function scoutsunite_droits_reunions() {
	return array(
		'edit_reunions',
		'edit_others_reunions',
		'edit_published_reunions',
		'edit_private_reunions',
		'publish_reunions',
		'read_private_reunions',
		'delete_reunions',
		'delete_others_reunions',
		'delete_published_reunions',
		'delete_private_reunions',
	);
}

/**
 * Droits sur les documents (type de contenu « document », inc/documents.php) : les mêmes que pour les réunions.
 *
 * @return array
 */
function scoutsunite_droits_documents() {
	return str_replace( 'reunions', 'documents', scoutsunite_droits_reunions() );
}

function scoutsunite_droits_roles() {
	if ( SCOUTSUNITE_DROITS_VERSION === (int) get_option( 'scoutsunite_droits_version' ) ) {
		return;
	}
	$reunions = array_fill_keys( array_merge( scoutsunite_droits_reunions(), scoutsunite_droits_documents() ), true );

	remove_role( 'scoutsunite_animateur' );
	add_role(
		'scoutsunite_animateur',
		'Animateur',
		array_merge(
			array(
				'read'                  => true,
				'upload_files'          => true,
				// Pages : limitées à celles de ses sections par scoutsunite_droits_limites().
				'edit_pages'            => true,
				'edit_others_pages'     => true,
				'edit_published_pages'  => true,
				// Sans ce droit, l'éditeur propose « Soumettre à la relecture » et la page quitte le site.
				// Il ne vaut que pour les pages de ses sections (map_meta_cap) ; créer une page reste interdit.
				'publish_pages'         => true,
				'scoutsunite_animateur' => true,
			),
			$reunions
		)
	);

	$editeur = get_role( 'editor' );
	$staff   = $editeur ? $editeur->capabilities : array( 'read' => true );
	// Sans HTML libre : un script glissé dans une page s'exécuterait chez l'administrateur qui l'ouvre.
	unset( $staff['unfiltered_html'] );
	remove_role( 'scoutsunite_staff' );
	add_role(
		'scoutsunite_staff',
		'Staff d’unité',
		array_merge( $staff, $reunions, array( 'manage_unite' => true ) )
	);

	foreach ( array( 'administrator', 'editor' ) as $nom ) {
		$role = get_role( $nom );
		if ( $role ) {
			foreach ( array_keys( $reunions ) as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$admin->add_cap( 'manage_unite' );
	}

	update_option( 'scoutsunite_droits_version', SCOUTSUNITE_DROITS_VERSION );
}
add_action( 'init', 'scoutsunite_droits_roles' );

/**
 * Sections qu'un animateur peut gérer (sections d'âge et clan IAmA).
 *
 * @return array Identifiant => nom affiché.
 */
function scoutsunite_droits_sections_possibles() {
	return array_diff_key( scoutsunite_agenda_sections(), array( 'unite' => '' ) );
}

/**
 * L'utilisateur est-il un animateur limité à ses sections ?
 *
 * @param int $user_id Utilisateur (0 : l'utilisateur connecté).
 * @return bool
 */
function scoutsunite_droits_est_limite( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();
	return $user_id && user_can( $user_id, 'scoutsunite_animateur' ) && ! user_can( $user_id, 'manage_unite' );
}

/**
 * Sections attribuées à un animateur.
 *
 * @param int $user_id Utilisateur (0 : l'utilisateur connecté).
 * @return string[] Identifiants de section.
 */
function scoutsunite_droits_sections( $user_id = 0 ) {
	$user_id  = $user_id ? $user_id : get_current_user_id();
	$sections = get_user_meta( $user_id, 'scoutsunite_sections', true );
	return array_values( array_intersect( (array) $sections, array_keys( scoutsunite_droits_sections_possibles() ) ) );
}

/**
 * Sections proposées dans le panneau d'une réunion : toutes, ou celles de l'animateur.
 *
 * @return array Identifiant => nom affiché.
 */
function scoutsunite_droits_sections_permises() {
	if ( ! scoutsunite_droits_est_limite() ) {
		return scoutsunite_agenda_sections();
	}
	return array_intersect_key( scoutsunite_agenda_sections(), array_flip( scoutsunite_droits_sections() ) );
}

/**
 * Nettoyage du champ « section » d'une réunion : un animateur ne peut pas
 * l'attribuer à une section qui n'est pas la sienne.
 *
 * @param string $valeur Section demandée.
 * @return string Section retenue.
 */
function scoutsunite_droits_section_permise( $valeur ) {
	$valeur = sanitize_text_field( $valeur );
	if ( ! scoutsunite_droits_est_limite() ) {
		return $valeur;
	}
	$miennes = scoutsunite_droits_sections();
	return in_array( $valeur, $miennes, true ) ? $valeur : (string) reset( $miennes );
}

/**
 * Le cœur des droits : un animateur ne touche qu'aux réunions, aux documents et aux pages de ses sections.
 *
 * @param string[] $caps    Droits nécessaires.
 * @param string   $cap     Droit demandé.
 * @param int      $user_id Utilisateur.
 * @param array    $args    Arguments (l'identifiant du contenu).
 * @return string[]
 */
function scoutsunite_droits_limites( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'read_post' ), true ) || ! scoutsunite_droits_est_limite( $user_id ) ) {
		return $caps;
	}
	$post = get_post( $args[0] ?? 0 );
	if ( ! $post ) {
		return $caps;
	}
	// Lire : tout ce qui est publié, mais les réunions et documents privés de ses sections seulement.
	if ( 'read_post' === $cap && ( 'publish' === $post->post_status || ! in_array( $post->post_type, array( 'reunion', 'document' ), true ) ) ) {
		return $caps;
	}
	$miennes = scoutsunite_droits_sections( $user_id );

	if ( in_array( $post->post_type, array( 'reunion', 'document' ), true ) ) {
		$section = get_post_meta( $post->ID, 'su_section', true );
		// Une réunion ou un document qu'il vient de créer n'a pas encore de section ; celle d'un autre, sans section, ne le regarde pas.
		$sienne_sans_section = '' === $section && (int) $post->post_author === (int) $user_id;
		if ( $sienne_sans_section || in_array( $section, $miennes, true ) ) {
			return $caps;
		}
		return array( 'do_not_allow' );
	}

	if ( 'page' === $post->post_type ) {
		if ( 'delete_post' !== $cap && in_array( $post->post_name, $miennes, true ) ) {
			return $caps;
		}
		return array( 'do_not_allow' );
	}

	return $caps;
}
add_filter( 'map_meta_cap', 'scoutsunite_droits_limites', 10, 4 );

/**
 * Dans l'administration, un animateur ne voit que les réunions et les pages de ses sections.
 *
 * @param WP_Query $query Requête.
 */
function scoutsunite_droits_listes( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || ! scoutsunite_droits_est_limite() ) {
		return;
	}
	$miennes = scoutsunite_droits_sections();
	if ( in_array( $query->get( 'post_type' ), array( 'reunion', 'document' ), true ) ) {
		$query->set(
			'meta_query',
			array(
				array(
					'key'     => 'su_section',
					'value'   => $miennes ? $miennes : array( '' ),
					'compare' => 'IN',
				),
			)
		);
	} elseif ( 'page' === $query->get( 'post_type' ) ) {
		$query->set( 'post_name__in', $miennes ? $miennes : array( '' ) );
	}
}
add_action( 'pre_get_posts', 'scoutsunite_droits_listes', 20 );

/**
 * Sur sa page de section, un animateur ne change que les textes et les images :
 * la mise en page est verrouillée.
 *
 * @param array                   $settings Réglages de l'éditeur.
 * @param WP_Block_Editor_Context $context  Contexte.
 * @return array
 */
function scoutsunite_droits_verrou( $settings, $context ) {
	if ( ! empty( $context->post ) && 'page' === $context->post->post_type && scoutsunite_droits_est_limite() ) {
		$settings['templateLock'] = 'contentOnly';
	}
	return $settings;
}
add_filter( 'block_editor_settings_all', 'scoutsunite_droits_verrou', 10, 2 );

/**
 * Un animateur sans section ne peut rien faire : on le lui dit.
 */
function scoutsunite_droits_avis() {
	if ( scoutsunite_droits_est_limite() && ! scoutsunite_droits_sections() ) {
		echo '<div class="notice notice-warning"><p>Aucune section ne t’est encore attribuée : demande à un administrateur de cocher ta section dans ton profil.</p></div>';
	}
}
add_action( 'admin_notices', 'scoutsunite_droits_avis' );

/**
 * Un animateur ne crée pas de page : il modifie seulement celles de ses sections.
 * Bloque la création par l'API (l'éditeur, une application) et l'écran « Ajouter une page ».
 *
 * @param stdClass|WP_Error $page    Page préparée pour l'enregistrement.
 * @param WP_REST_Request   $request Requête.
 * @return stdClass|WP_Error
 */
function scoutsunite_droits_pas_de_nouvelle_page( $page, $request ) {
	if ( ! is_wp_error( $page ) && empty( $page->ID ) && scoutsunite_droits_est_limite() ) {
		return new WP_Error( 'rest_cannot_create', 'Un animateur ne peut pas créer de page.', array( 'status' => 403 ) );
	}
	return $page;
}
add_filter( 'rest_pre_insert_page', 'scoutsunite_droits_pas_de_nouvelle_page', 10, 2 );

function scoutsunite_droits_ecran_nouvelle_page() {
	if ( isset( $_GET['post_type'] ) && 'page' === $_GET['post_type'] && scoutsunite_droits_est_limite() ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_die( 'Un animateur ne peut pas créer de page : il modifie la page de sa section.', '', array( 'response' => 403, 'back_link' => true ) );
	}
}
add_action( 'load-post-new.php', 'scoutsunite_droits_ecran_nouvelle_page' );

function scoutsunite_droits_menu() {
	if ( scoutsunite_droits_est_limite() ) {
		remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );
	}
}
add_action( 'admin_menu', 'scoutsunite_droits_menu', 99 );

/**
 * Quelle que soit la voie (éditeur, API, XML-RPC, application), un animateur ne crée jamais de page.
 * WordPress abandonne l'enregistrement quand ce filtre répond « vide ».
 *
 * @param bool  $vide    Le contenu est-il considéré comme vide ?
 * @param array $postarr Données reçues.
 * @return bool
 */
function scoutsunite_droits_aucune_page_creee( $vide, $postarr ) {
	if ( empty( $postarr['ID'] ) && 'page' === ( $postarr['post_type'] ?? '' ) && scoutsunite_droits_est_limite() ) {
		return true;
	}
	return $vide;
}
add_filter( 'wp_insert_post_empty_content', 'scoutsunite_droits_aucune_page_creee', 10, 2 );

/**
 * Sur la page de sa section, un animateur change le contenu, rien d'autre : l'état, l'adresse,
 * la page parente, le mot de passe et l'auteur restent ceux d'avant, par toutes les voies.
 * Ainsi « Soumettre à la relecture », un brouillon ou un déplacement ne la font plus disparaître.
 *
 * @param array $data    Données de la page.
 * @param array $postarr Données reçues, avec l'ID.
 * @return array
 */
function scoutsunite_droits_garde_publiee( $data, $postarr ) {
	if ( 'page' !== $data['post_type'] || empty( $postarr['ID'] ) || ! scoutsunite_droits_est_limite() ) {
		return $data;
	}
	$avant = get_post( $postarr['ID'] );
	if ( ! $avant || 'page' !== $avant->post_type ) {
		return $data;
	}
	foreach ( array( 'post_status', 'post_name', 'post_parent', 'post_password', 'post_author' ) as $champ ) {
		$data[ $champ ] = $avant->$champ;
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'scoutsunite_droits_garde_publiee', 10, 2 );

/**
 * Le modèle de page non plus : il ne change pas sous la main d'un animateur.
 *
 * @param null|bool $court     Null pour laisser faire WordPress.
 * @param int       $object_id Contenu.
 * @param string    $meta_key  Champ.
 * @return null|bool
 */
function scoutsunite_droits_garde_modele( $court, $object_id, $meta_key ) {
	if ( '_wp_page_template' === $meta_key && 'page' === get_post_type( $object_id ) && scoutsunite_droits_est_limite() ) {
		return false;
	}
	return $court;
}
add_filter( 'add_post_metadata', 'scoutsunite_droits_garde_modele', 10, 3 );
add_filter( 'update_post_metadata', 'scoutsunite_droits_garde_modele', 10, 3 );
add_filter( 'delete_post_metadata', 'scoutsunite_droits_garde_modele', 10, 3 );

/* ---------------------------------------------------------------------------
   Profil : les sections de l'animateur
   ------------------------------------------------------------------------ */

/**
 * Cases à cocher des sections, dans le profil d'un animateur.
 *
 * @param WP_User $user Utilisateur affiché.
 */
function scoutsunite_droits_profil( $user ) {
	if ( ! user_can( $user, 'scoutsunite_animateur' ) && ! current_user_can( 'promote_users' ) ) {
		return;
	}
	$miennes    = scoutsunite_droits_sections( $user->ID );
	$modifiable = current_user_can( 'promote_users' );
	?>
	<h2>Sections de l’animateur</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">Sections</th>
			<td>
				<fieldset>
					<?php foreach ( scoutsunite_droits_sections_possibles() as $slug => $nom ) : ?>
						<label style="display:block;margin-bottom:4px">
							<input type="checkbox" name="scoutsunite_sections[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $miennes, true ) ); ?> <?php disabled( ! $modifiable ); ?>>
							<?php echo esc_html( $nom ); ?>
						</label>
					<?php endforeach; ?>
					<p class="description">Avec le rôle « Animateur », cette personne gère les réunions et les documents de ces sections et les textes de leur page, rien d’autre.</p>
				</fieldset>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'scoutsunite_droits_profil' );
add_action( 'edit_user_profile', 'scoutsunite_droits_profil' );

/**
 * Enregistrement des sections (par un administrateur seulement).
 *
 * @param int $user_id Utilisateur modifié.
 */
function scoutsunite_droits_profil_enregistrer( $user_id ) {
	if ( ! current_user_can( 'promote_users' ) || ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}
	$demandees = isset( $_POST['scoutsunite_sections'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['scoutsunite_sections'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification -- vérifié par WordPress avant ce crochet.
	update_user_meta( $user_id, 'scoutsunite_sections', array_values( array_intersect( $demandees, array_keys( scoutsunite_droits_sections_possibles() ) ) ) );
}
add_action( 'personal_options_update', 'scoutsunite_droits_profil_enregistrer' );
add_action( 'edit_user_profile_update', 'scoutsunite_droits_profil_enregistrer' );
