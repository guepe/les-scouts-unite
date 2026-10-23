<?php
/**
 * Droits d'accès : les rôles « Animateur » et « Staff d'unité ».
 *
 * Un animateur est rattaché à une ou plusieurs sections (dans son profil, par un
 * administrateur). Il gère les réunions de ses sections et modifie les textes et
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
const SCOUTSUNITE_DROITS_VERSION = 1;

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

function scoutsunite_droits_roles() {
	if ( SCOUTSUNITE_DROITS_VERSION === (int) get_option( 'scoutsunite_droits_version' ) ) {
		return;
	}
	$reunions = array_fill_keys( scoutsunite_droits_reunions(), true );

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
				'scoutsunite_animateur' => true,
			),
			$reunions
		)
	);

	$editeur = get_role( 'editor' );
	remove_role( 'scoutsunite_staff' );
	add_role(
		'scoutsunite_staff',
		'Staff d’unité',
		array_merge( $editeur ? $editeur->capabilities : array( 'read' => true ), $reunions, array( 'manage_unite' => true ) )
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
 * Le cœur des droits : un animateur ne touche qu'aux réunions et aux pages de ses sections.
 *
 * @param string[] $caps    Droits nécessaires.
 * @param string   $cap     Droit demandé.
 * @param int      $user_id Utilisateur.
 * @param array    $args    Arguments (l'identifiant du contenu).
 * @return string[]
 */
function scoutsunite_droits_limites( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post' ), true ) || ! scoutsunite_droits_est_limite( $user_id ) ) {
		return $caps;
	}
	$post = get_post( $args[0] ?? 0 );
	if ( ! $post ) {
		return $caps;
	}
	$miennes = scoutsunite_droits_sections( $user_id );

	if ( 'reunion' === $post->post_type ) {
		$section = get_post_meta( $post->ID, 'su_section', true );
		// Une réunion qu'il vient de créer n'a pas encore de section.
		if ( '' === $section || in_array( $section, $miennes, true ) ) {
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
	if ( 'reunion' === $query->get( 'post_type' ) ) {
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
					<p class="description">Avec le rôle « Animateur », cette personne gère les réunions de ces sections et les textes de leur page, rien d’autre.</p>
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
