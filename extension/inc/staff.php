<?php
/**
 * Le staff : une fiche par animateur (sections, staff d'unité, clan IAmA) et sa carte à imprimer.
 *
 * Les fiches vivent dans l'administration seulement (Staff → Ajouter) : pas d'adresse
 * publique, rien dans l'API. Le bloc « Staff de la section », la page Contact et les
 * feuilles pour le frigo en tirent ce qu'ils montraient déjà (totem, prénom, fonction,
 * GSM). Le nom, le quali, le totem de meute et la photo ne servent qu'à la carte.
 *
 * La photo n'est pas dans la médiathèque : elle est rangée dans un dossier protégé
 * et servie seulement aux personnes connectées qui peuvent voir la fiche.
 *
 * Droits (avec inc/droits.php) : les administrateurs et le staff d'unité gèrent toutes
 * les fiches et impriment les cartes ; un animateur dont le compte est relié à une fiche
 * modifie celle-là seulement, sans changer sa section, son rôle de chef ni son état.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sections d'une fiche : les sections d'âge, le staff d'unité et le clan IAmA.
 *
 * @return array Identifiant => nom affiché.
 */
function scoutsunite_staff_sections() {
	$sections         = scoutsunite_agenda_sections();
	$sections['unite'] = 'Staff d’unité';
	return $sections;
}

/**
 * Ce que la carte écrit pour une section : son staff, l'intitulé du chef et le logo.
 *
 * @param string $section Identifiant de section.
 * @return array
 */
function scoutsunite_staff_section_infos( $section ) {
	$infos = array(
		'baladins'   => array( 'Staff de la Ronde', 'de Ronde', 'logos/logo_Baladins.png' ),
		'louveteaux' => array( 'Staff de la Meute', 'de meute', 'logos/logo_Louveteaux.png' ),
		'eclaireurs' => array( 'Staff de la Troupe', 'de troupe', 'logos/logo_Eclaireurs.png' ),
		'pionniers'  => array( 'Staff du Poste', 'de poste', 'logos/logo_Pionniers.png' ),
		'unite'      => array( 'Staff d’unité', '', 'logos/logo_Federation_couleur_horizontal.png' ),
		'iama'       => array( 'Le clan IAmA', '', 'logos/logo_IAmA_blanc.png' ),
	);
	$i     = $infos[ $section ] ?? $infos['unite'];
	return array(
		'nom'   => scoutsunite_agenda_sections()[ $section ] ?? 'Toute l’unité',
		'staff' => $i[0],
		'de'    => $i[1],
		'logo'  => $i[2],
	);
}

/* ---------------------------------------------------------------------------
   Type de contenu
   ------------------------------------------------------------------------ */

function scoutsunite_staff_register() {
	register_post_type(
		'animateur',
		array(
			'labels'              => array(
				'name'               => 'Staff',
				'singular_name'      => 'Fiche',
				'menu_name'          => 'Staff',
				'all_items'          => 'Toutes les fiches',
				'add_new'            => 'Ajouter',
				'add_new_item'       => 'Ajouter une fiche',
				'edit_item'          => 'Modifier la fiche',
				'new_item'           => 'Nouvelle fiche',
				'search_items'       => 'Rechercher une fiche',
				'not_found'          => 'Aucune fiche',
				'not_found_in_trash' => 'Aucune fiche dans la corbeille',
			),
			// Des données personnelles : aucune page, aucune recherche, rien dans l'API.
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'show_in_nav_menus'   => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_icon'           => 'dashicons-id',
			'menu_position'       => 7,
			'capability_type'     => array( 'animateur', 'animateurs' ),
			'capabilities'        => array( 'create_posts' => 'manage_unite' ),
			'map_meta_cap'        => true,
			'supports'            => array( 'revisions' ),
		)
	);
}
add_action( 'init', 'scoutsunite_staff_register' );

/**
 * Champs d'une fiche (méta « su_* ») : clé => libellé.
 *
 * @return array
 */
function scoutsunite_staff_champs() {
	return array(
		'su_prenom'      => 'Prénom',
		'su_nom'         => 'Nom',
		'su_totem'       => 'Totem',
		'su_quali'       => 'Quali',
		'su_totem_meute' => 'Totem de meute',
		'su_genre'       => 'Il ou elle',
		'su_fonction'    => 'Fonction',
		'su_gsm'         => 'GSM',
	);
}

/**
 * Champs réservés aux administrateurs et au staff d'unité : un animateur ne les change pas sur sa fiche.
 *
 * @return string[]
 */
function scoutsunite_staff_champs_reserves() {
	return array( 'su_section', 'su_chef', 'su_ancien', 'su_compte' );
}

/* ---------------------------------------------------------------------------
   Lecture
   ------------------------------------------------------------------------ */

/**
 * Une fiche, prête à afficher.
 *
 * @param WP_Post|int $post Fiche.
 * @return array
 */
function scoutsunite_staff_fiche( $post ) {
	$post  = get_post( $post );
	$fiche = array(
		'id'      => $post->ID,
		'section' => (string) get_post_meta( $post->ID, 'su_section', true ),
		'chef'    => '1' === get_post_meta( $post->ID, 'su_chef', true ),
		'ancien'  => '1' === get_post_meta( $post->ID, 'su_ancien', true ),
		'compte'  => (int) get_post_meta( $post->ID, 'su_compte', true ),
		'photo'   => (string) get_post_meta( $post->ID, 'su_photo', true ),
		'ordre'   => (int) $post->menu_order,
	);
	foreach ( array_keys( scoutsunite_staff_champs() ) as $cle ) {
		$fiche[ substr( $cle, 3 ) ] = trim( (string) get_post_meta( $post->ID, $cle, true ) );
	}
	return $fiche;
}

/**
 * Fiches d'une section, chef en tête, puis dans l'ordre choisi, puis par totem et prénom.
 *
 * @param string $section Identifiant de section, '' pour toutes.
 * @param bool   $anciens Garder aussi les anciens (sinon, seulement le staff de cette année).
 * @return array[] Fiches.
 */
function scoutsunite_staff_fiches( $section = '', $anciens = false ) {
	$args = array(
		'post_type'      => 'animateur',
		'post_status'    => 'publish',
		'posts_per_page' => 300,
		'no_found_rows'  => true,
	);
	if ( '' !== $section ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery -- quelques dizaines de fiches.
			array(
				'key'   => 'su_section',
				'value' => $section,
			),
		);
	}
	$fiches = array_map( 'scoutsunite_staff_fiche', get_posts( $args ) );
	if ( ! $anciens ) {
		$fiches = array_values(
			array_filter(
				$fiches,
				function ( $f ) {
					return ! $f['ancien'];
				}
			)
		);
	}
	$sections = array_keys( scoutsunite_staff_sections() );
	usort(
		$fiches,
		function ( $a, $b ) use ( $sections ) {
			$cle = function ( $f ) use ( $sections ) {
				$rang = array_search( $f['section'], $sections, true );
				return array( (int) $f['ancien'], false === $rang ? 99 : $rang, $f['chef'] ? 0 : 1, $f['ordre'], remove_accents( mb_strtolower( scoutsunite_staff_totem( $f ) . ' ' . $f['prenom'] ) ) );
			};
			return $cle( $a ) <=> $cle( $b );
		}
	);
	return $fiches;
}

/**
 * Le rôle écrit au verso de la carte : « Cheffe de meute », « Animateur », « Responsable d’unité »…
 *
 * @param array $fiche Fiche.
 * @return string
 */
function scoutsunite_staff_role( $fiche ) {
	$elle = 'f' === $fiche['genre'];
	if ( 'unite' === $fiche['section'] ) {
		return $fiche['chef'] ? 'Responsable d’unité' : 'Staff d’unité';
	}
	if ( 'iama' === $fiche['section'] ) {
		return $fiche['chef'] ? 'Responsable IAmA' : 'Membre du clan IAmA';
	}
	if ( $fiche['chef'] ) {
		return ( $elle ? 'Cheffe ' : 'Chef ' ) . scoutsunite_staff_section_infos( $fiche['section'] )['de'];
	}
	return $elle ? 'Animatrice' : 'Animateur';
}

/**
 * Le totem que voient les enfants : chez les Louveteaux, le totem de meute s'il est rempli.
 *
 * @param array $fiche Fiche.
 * @return string
 */
function scoutsunite_staff_totem( $fiche ) {
	if ( 'louveteaux' === ( $fiche['section'] ?? '' ) && '' !== ( $fiche['totem_meute'] ?? '' ) ) {
		return $fiche['totem_meute'];
	}
	return $fiche['totem'];
}

/**
 * « Totem – Prénom », ou le prénom seul.
 *
 * @param array $fiche Fiche.
 * @return string
 */
function scoutsunite_staff_appel( $fiche ) {
	$totem = scoutsunite_staff_totem( $fiche );
	return '' !== $totem ? $totem . ' – ' . $fiche['prenom'] : $fiche['prenom'];
}

/* ---------------------------------------------------------------------------
   Droits : un animateur relié à une fiche ne touche qu'à la sienne
   ------------------------------------------------------------------------ */

/**
 * Sans « Réglages → Unité », on ne voit et ne modifie que sa propre fiche, et on n'en supprime aucune.
 *
 * @param string[] $caps    Droits nécessaires.
 * @param string   $cap     Droit demandé.
 * @param int      $user_id Utilisateur.
 * @param array    $args    Arguments (l'identifiant de la fiche).
 * @return string[]
 */
function scoutsunite_staff_droits( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'publish_post', 'read_post' ), true ) || user_can( $user_id, 'manage_unite' ) ) {
		return $caps;
	}
	$post = get_post( $args[0] ?? 0 );
	if ( ! $post || 'animateur' !== $post->post_type ) {
		return $caps;
	}
	$sienne = $user_id && (int) get_post_meta( $post->ID, 'su_compte', true ) === (int) $user_id;
	if ( $sienne && in_array( $cap, array( 'edit_post', 'read_post' ), true ) ) {
		return $caps;
	}
	return array( 'do_not_allow' );
}
add_filter( 'map_meta_cap', 'scoutsunite_staff_droits', 10, 4 );

/**
 * Dans la liste, un animateur ne voit que sa fiche.
 *
 * @param WP_Query $query Requête.
 */
function scoutsunite_staff_liste_limitee( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'animateur' !== $query->get( 'post_type' ) || current_user_can( 'manage_unite' ) ) {
		return;
	}
	$query->set(
		'meta_query',
		array(
			array(
				'key'   => 'su_compte',
				'value' => (string) get_current_user_id(),
			),
		)
	);
}
add_action( 'pre_get_posts', 'scoutsunite_staff_liste_limitee', 20 );

/**
 * Une fiche est toujours « publiée » : son état (cette année ou ancien) est un champ à part.
 * Un brouillon ou une relecture la ferait disparaître du site sans qu'on comprenne pourquoi.
 *
 * @param array $data Données de la fiche.
 * @return array
 */
function scoutsunite_staff_toujours_publiee( $data ) {
	if ( 'animateur' === $data['post_type'] && ! in_array( $data['post_status'], array( 'auto-draft', 'trash', 'inherit' ), true ) ) {
		$data['post_status']   = 'publish';
		$data['post_password'] = '';
	}
	return $data;
}
add_filter( 'wp_insert_post_data', 'scoutsunite_staff_toujours_publiee' );

/* ---------------------------------------------------------------------------
   La photo, dans un dossier protégé
   ------------------------------------------------------------------------ */

/**
 * Dossier des photos, créé au besoin avec ce qu'il faut pour qu'Apache refuse de le servir.
 * Les noms de fichiers sont aussi tirés au hasard : sur un autre serveur, on ne les devine pas.
 *
 * @return string Chemin, sans barre finale.
 */
function scoutsunite_staff_dossier() {
	$dossier = wp_upload_dir( null, false )['basedir'] . '/scoutsunite-staff';
	if ( ! is_dir( $dossier ) ) {
		wp_mkdir_p( $dossier );
	}
	if ( ! file_exists( $dossier . '/.htaccess' ) ) {
		file_put_contents( $dossier . '/.htaccess', "Require all denied\nDeny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- un petit fichier de protection.
		file_put_contents( $dossier . '/index.php', "<?php\n// Rien à voir.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	return $dossier;
}

/**
 * Chemin de la photo d'une fiche, '' s'il n'y en a pas.
 *
 * @param int $post_id Fiche.
 * @return string
 */
function scoutsunite_staff_photo_chemin( $post_id ) {
	$nom = basename( (string) get_post_meta( $post_id, 'su_photo', true ) );
	if ( '' === $nom ) {
		return '';
	}
	$chemin = scoutsunite_staff_dossier() . '/' . $nom;
	return is_readable( $chemin ) ? $chemin : '';
}

/**
 * Adresse de la photo (servie par l'administration, après vérification des droits), '' sans photo.
 *
 * @param int $post_id Fiche.
 * @return string
 */
function scoutsunite_staff_photo_url( $post_id ) {
	$chemin = scoutsunite_staff_photo_chemin( $post_id );
	if ( '' === $chemin ) {
		return '';
	}
	return add_query_arg(
		array(
			'action' => 'scoutsunite_photo',
			'fiche'  => $post_id,
			'v'      => filemtime( $chemin ),
		),
		admin_url( 'admin-post.php' )
	);
}

/**
 * Enregistre la photo envoyée pour une fiche : une image, réduite à 600 px, en JPEG.
 *
 * @param int   $post_id Fiche.
 * @param array $fichier Entrée de $_FILES.
 * @return string Message d'erreur, '' si tout va bien.
 */
function scoutsunite_staff_photo_enregistrer( $post_id, $fichier ) {
	if ( empty( $fichier['tmp_name'] ) || ! is_uploaded_file( $fichier['tmp_name'] ) ) {
		return '';
	}
	if ( ! empty( $fichier['error'] ) || $fichier['size'] > 8 * MB_IN_BYTES ) {
		return 'La photo n’a pas été enregistrée : le fichier est trop lourd (8 Mo au plus).';
	}
	$type = wp_check_filetype_and_ext( $fichier['tmp_name'], $fichier['name'], array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp' ) );
	if ( empty( $type['type'] ) || ! @getimagesize( $fichier['tmp_name'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors -- un fichier qui n'est pas une image.
		return 'La photo n’a pas été enregistrée : choisis une image JPEG, PNG ou WebP.';
	}
	$editeur = wp_get_image_editor( $fichier['tmp_name'] );
	if ( is_wp_error( $editeur ) ) {
		return 'La photo n’a pas été enregistrée : le serveur ne sait pas lire cette image.';
	}
	$editeur->resize( 600, 600, false );
	$editeur->set_quality( 85 );
	$nom    = $post_id . '-' . wp_generate_password( 24, false ) . '.jpg';
	$resultat = $editeur->save( scoutsunite_staff_dossier() . '/' . $nom, 'image/jpeg' );
	if ( is_wp_error( $resultat ) ) {
		return 'La photo n’a pas été enregistrée : le dossier des photos n’est pas accessible en écriture.';
	}
	scoutsunite_staff_photo_supprimer( $post_id );
	update_post_meta( $post_id, 'su_photo', $nom );
	return '';
}

/**
 * Supprime la photo d'une fiche.
 *
 * @param int $post_id Fiche.
 */
function scoutsunite_staff_photo_supprimer( $post_id ) {
	$chemin = scoutsunite_staff_photo_chemin( $post_id );
	if ( '' !== $chemin ) {
		wp_delete_file( $chemin );
	}
	delete_post_meta( $post_id, 'su_photo' );
}

/**
 * Une fiche supprimée pour de bon emporte sa photo.
 *
 * @param int $post_id Contenu supprimé.
 */
function scoutsunite_staff_suppression( $post_id ) {
	if ( 'animateur' === get_post_type( $post_id ) ) {
		scoutsunite_staff_photo_supprimer( $post_id );
	}
}
add_action( 'before_delete_post', 'scoutsunite_staff_suppression' );

/**
 * Sert la photo d'une fiche à qui peut la voir.
 */
function scoutsunite_staff_photo_servir() {
	$post_id = isset( $_GET['fiche'] ) ? absint( $_GET['fiche'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification -- lecture seule, droits vérifiés.
	if ( ! $post_id || 'animateur' !== get_post_type( $post_id ) || ! current_user_can( 'read_post', $post_id ) ) {
		wp_die( 'Tu n’as pas accès à cette photo.', '', array( 'response' => 403 ) );
	}
	$chemin = scoutsunite_staff_photo_chemin( $post_id );
	if ( '' === $chemin ) {
		status_header( 404 );
		exit;
	}
	header( 'Content-Type: image/jpeg' );
	header( 'Content-Length: ' . filesize( $chemin ) );
	header( 'Cache-Control: private, max-age=86400' );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $chemin ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}
add_action( 'admin_post_scoutsunite_photo', 'scoutsunite_staff_photo_servir' );

/* ---------------------------------------------------------------------------
   Formulaire
   ------------------------------------------------------------------------ */

function scoutsunite_staff_boite() {
	remove_meta_box( 'submitdiv', 'animateur', 'side' );
	add_meta_box( 'scoutsunite-fiche', 'La fiche', 'scoutsunite_staff_formulaire', 'animateur', 'normal', 'high' );
	add_meta_box( 'scoutsunite-fiche-enregistrer', 'Enregistrer', 'scoutsunite_staff_formulaire_enregistrer', 'animateur', 'side', 'high' );
}
add_action( 'add_meta_boxes_animateur', 'scoutsunite_staff_boite' );

/**
 * Le formulaire envoie aussi la photo.
 *
 * @param WP_Post $post Contenu affiché.
 */
function scoutsunite_staff_formulaire_fichiers( $post ) {
	if ( 'animateur' === $post->post_type ) {
		echo ' enctype="multipart/form-data"';
	}
}
add_action( 'post_edit_form_tag', 'scoutsunite_staff_formulaire_fichiers' );

/**
 * Bouton d'enregistrement (à la place de la boîte « Publier » : une fiche n'a pas de brouillon).
 *
 * @param WP_Post $post Fiche.
 */
function scoutsunite_staff_formulaire_enregistrer( $post ) {
	?>
	<input type="hidden" name="post_status" value="publish">
	<?php submit_button( 'auto-draft' === $post->post_status ? 'Créer la fiche' : 'Enregistrer la fiche', 'primary large', 'publish', false ); ?>
	<?php if ( current_user_can( 'manage_unite' ) && 'auto-draft' !== $post->post_status ) : ?>
		<p><a href="<?php echo esc_url( scoutsunite_staff_cartes_url( array( 'fiche' => $post->ID ) ) ); ?>" target="_blank">Imprimer sa carte</a></p>
		<?php if ( current_user_can( 'delete_post', $post->ID ) ) : ?>
			<p><a class="submitdelete" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>">Mettre à la corbeille</a></p>
			<p class="description">Un animateur qui part : coche plutôt « Ancien » pour garder sa fiche.</p>
		<?php endif; ?>
	<?php endif; ?>
	<?php
}

/**
 * Champs de la fiche.
 *
 * @param WP_Post $post Fiche.
 */
function scoutsunite_staff_formulaire( $post ) {
	wp_nonce_field( 'scoutsunite_fiche', 'scoutsunite_fiche_nonce' );
	$f       = scoutsunite_staff_fiche( $post );
	$gerer   = current_user_can( 'manage_unite' );
	$photo   = scoutsunite_staff_photo_url( $post->ID );
	$texte   = function ( $cle, $libelle, $aide = '', $exemple = '', $type = 'text' ) use ( $f ) {
		$id = 'su-' . str_replace( '_', '-', $cle );
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $libelle ) . '</label></th><td>';
		echo '<input class="regular-text" type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="su_' . esc_attr( $cle ) . '" value="' . esc_attr( $f[ $cle ] ) . '" placeholder="' . esc_attr( $exemple ) . '">';
		echo $aide ? '<p class="description">' . esc_html( $aide ) . '</p>' : '';
		echo '</td></tr>';
	};
	?>
	<p>Le totem, le prénom, la fonction et le GSM s’affichent sur la page de la section et sur les feuilles pour le frigo. Le nom, le quali, le totem de meute et la photo ne servent qu’à la carte : ils ne sont jamais publiés sur le site.</p>
	<table class="form-table" role="presentation">
		<?php
		$texte( 'prenom', 'Prénom', '', 'Marie' );
		$texte( 'nom', 'Nom', 'Écrit en tout petit sur la carte.', 'Dupont' );
		$texte( 'totem', 'Totem', 'Laisse vide si la personne n’a pas encore de totem.', 'Castor' );
		$texte( 'quali', 'Quali', '', 'Jovial' );
		?>
		<tr class="su-meute">
			<th scope="row"><label for="su-totem-meute">Totem de meute</label></th>
			<td>
				<input class="regular-text" type="text" id="su-totem-meute" name="su_totem_meute" value="<?php echo esc_attr( $f['totem_meute'] ); ?>" placeholder="Bagheera">
				<p class="description">Le nom de la Jungle, pour les animateurs Louveteaux : c’est lui qui est écrit en grand sur la carte.</p>
			</td>
		</tr>
		<tr>
			<th scope="row">Il ou elle</th>
			<td>
				<label><input type="radio" name="su_genre" value="m" <?php checked( 'f' !== $f['genre'] ); ?>> Il (chef, animateur)</label>
				<label style="margin-left:1em"><input type="radio" name="su_genre" value="f" <?php checked( 'f', $f['genre'] ); ?>> Elle (cheffe, animatrice)</label>
				<p class="description">Pour écrire le rôle sur la carte.</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="su-section">Section</label></th>
			<td>
				<select id="su-section" name="su_section" <?php disabled( ! $gerer ); ?>>
					<?php foreach ( scoutsunite_staff_sections() as $slug => $nom ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $f['section'], $slug ); ?>><?php echo esc_html( $nom ); ?></option>
					<?php endforeach; ?>
				</select>
				<label style="margin-left:1em"><input type="checkbox" name="su_chef" value="1" <?php checked( $f['chef'] ); ?> <?php disabled( ! $gerer ); ?>> Chef ou cheffe (responsable d’unité pour le staff d’unité)</label>
				<?php if ( ! $gerer ) : ?>
					<p class="description">La section et le rôle de chef sont changés par le staff d’unité.</p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		$texte( 'fonction', 'Fonction', 'Facultatif : santé, trésorerie, communication…', 'santé' );
		$texte( 'gsm', 'GSM', 'Visible par tout le monde sur le site, les feuilles pour le frigo et la carte.', '0470 12 34 56', 'tel' );
		?>
		<tr>
			<th scope="row"><label for="su-photo">Photo</label></th>
			<td>
				<?php if ( $photo ) : ?>
					<img src="<?php echo esc_url( $photo ); ?>" alt="" style="display:block;width:110px;height:140px;object-fit:cover;border-radius:8px;margin-bottom:8px">
					<label><input type="checkbox" name="su_photo_supprimer" value="1"> Supprimer la photo</label><br>
				<?php endif; ?>
				<input type="file" id="su-photo" name="su_photo" accept="image/jpeg,image/png,image/webp">
				<p class="description">Pour la carte seulement : elle n’est jamais publiée sur le site. Une photo en hauteur, le visage bien au centre.</p>
			</td>
		</tr>
		<?php if ( $gerer ) : ?>
			<tr>
				<th scope="row"><label for="su-ordre">Ordre</label></th>
				<td>
					<input class="small-text" type="number" id="su-ordre" name="su_ordre" value="<?php echo esc_attr( (string) $f['ordre'] ); ?>">
					<p class="description">Les plus petits nombres en premier, après le chef ou la cheffe.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="su-compte">Compte du site</label></th>
				<td>
					<?php
					wp_dropdown_users(
						array(
							'name'              => 'su_compte',
							'id'                => 'su-compte',
							'selected'          => $f['compte'],
							'show_option_none'  => '— Aucun compte —',
							'option_none_value' => '0',
							'show'              => 'display_name_with_login',
						)
					);
					?>
					<p class="description">Facultatif : la personne pourra modifier elle-même cette fiche (pas sa section ni son rôle).</p>
				</td>
			</tr>
			<tr>
				<th scope="row">Ancien</th>
				<td>
					<label><input type="checkbox" name="su_ancien" value="1" <?php checked( $f['ancien'] ); ?>> N’est plus dans le staff</label>
					<p class="description">La fiche est gardée, mais elle ne s’affiche plus sur le site et ses cartes ne sont plus imprimées avec celles de la section.</p>
				</td>
			</tr>
		<?php endif; ?>
	</table>
	<script>
	( function () {
		var section = document.getElementById( 'su-section' );
		var meute = document.querySelector( '.su-meute' );
		function montrer() {
			meute.style.display = 'louveteaux' === section.value ? '' : 'none';
		}
		section.addEventListener( 'change', montrer );
		montrer();
	} )();
	</script>
	<?php
}

/**
 * Enregistrement de la fiche.
 *
 * @param int $post_id Fiche.
 */
function scoutsunite_staff_enregistrer( $post_id ) {
	if ( ! isset( $_POST['scoutsunite_fiche_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['scoutsunite_fiche_nonce'] ) ), 'scoutsunite_fiche' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array_keys( scoutsunite_staff_champs() ) as $cle ) {
		if ( 'su_genre' !== $cle && isset( $_POST[ $cle ] ) ) {
			update_post_meta( $post_id, $cle, sanitize_text_field( wp_unslash( $_POST[ $cle ] ) ) );
		}
	}
	update_post_meta( $post_id, 'su_genre', isset( $_POST['su_genre'] ) && 'f' === $_POST['su_genre'] ? 'f' : 'm' );

	if ( current_user_can( 'manage_unite' ) ) {
		$section = isset( $_POST['su_section'] ) ? sanitize_key( wp_unslash( $_POST['su_section'] ) ) : '';
		update_post_meta( $post_id, 'su_section', isset( scoutsunite_staff_sections()[ $section ] ) ? $section : 'unite' );
		update_post_meta( $post_id, 'su_chef', empty( $_POST['su_chef'] ) ? '' : '1' );
		update_post_meta( $post_id, 'su_ancien', empty( $_POST['su_ancien'] ) ? '' : '1' );
		$compte = isset( $_POST['su_compte'] ) ? absint( $_POST['su_compte'] ) : 0;
		update_post_meta( $post_id, 'su_compte', $compte && get_userdata( $compte ) ? (string) $compte : '' );
		if ( isset( $_POST['su_ordre'] ) ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'menu_order' => (int) $_POST['su_ordre'] ), array( 'ID' => $post_id ) );
		}
	}

	if ( ! empty( $_POST['su_photo_supprimer'] ) ) {
		scoutsunite_staff_photo_supprimer( $post_id );
	}
	if ( isset( $_FILES['su_photo'] ) ) {
		$erreur = scoutsunite_staff_photo_enregistrer( $post_id, $_FILES['su_photo'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- fichier vérifié par scoutsunite_staff_photo_enregistrer().
		if ( '' !== $erreur ) {
			set_transient( 'scoutsunite_fiche_erreur_' . get_current_user_id(), $erreur, 60 );
		}
	}
	scoutsunite_staff_titre( $post_id );
}
add_action( 'save_post_animateur', 'scoutsunite_staff_enregistrer' );

/**
 * Le titre de la fiche, pour la liste et la recherche : « Totem Quali – Prénom Nom ».
 *
 * @param int $post_id Fiche.
 */
function scoutsunite_staff_titre( $post_id ) {
	global $wpdb;
	$f     = scoutsunite_staff_fiche( $post_id );
	$titre = trim( trim( $f['totem'] . ' ' . $f['quali'] ) . ' – ' . trim( $f['prenom'] . ' ' . $f['nom'] ), ' –' );
	$wpdb->update(
		$wpdb->posts,
		array(
			'post_title' => '' !== $titre ? $titre : 'Fiche sans nom',
			'post_name'  => 'fiche-' . $post_id,
		),
		array( 'ID' => $post_id )
	);
	clean_post_cache( $post_id );
}

/**
 * Message si la photo n'a pas pu être enregistrée.
 */
function scoutsunite_staff_avis() {
	$cle    = 'scoutsunite_fiche_erreur_' . get_current_user_id();
	$erreur = get_transient( $cle );
	if ( $erreur ) {
		delete_transient( $cle );
		echo '<div class="notice notice-error"><p>' . esc_html( $erreur ) . '</p></div>';
	}
	$ecran = get_current_screen();
	if ( $ecran && 'edit-animateur' === $ecran->id && current_user_can( 'manage_unite' ) ) {
		$liens = array( '<a href="' . esc_url( scoutsunite_staff_cartes_url() ) . '" target="_blank">tout le staff</a>' );
		foreach ( scoutsunite_staff_sections() as $slug => $nom ) {
			$liens[] = '<a href="' . esc_url( scoutsunite_staff_cartes_url( array( 'section' => $slug ) ) ) . '" target="_blank">' . esc_html( $nom ) . '</a>';
		}
		echo '<div class="notice notice-info"><p><strong>Imprimer les cartes :</strong> ' . implode( ' · ', $liens ) . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- échappé à la construction.
	}
}
add_action( 'admin_notices', 'scoutsunite_staff_avis' );

/* ---------------------------------------------------------------------------
   Liste dans l'administration
   ------------------------------------------------------------------------ */

function scoutsunite_staff_colonnes( $columns ) {
	return array(
		'cb'         => $columns['cb'] ?? '',
		'title'      => 'Nom',
		'su_section' => 'Section',
		'su_role'    => 'Rôle',
		'su_gsm'     => 'GSM',
		'su_photo'   => 'Photo',
	);
}
add_filter( 'manage_animateur_posts_columns', 'scoutsunite_staff_colonnes' );

function scoutsunite_staff_colonne( $column, $post_id ) {
	$f = scoutsunite_staff_fiche( $post_id );
	if ( 'su_section' === $column ) {
		echo esc_html( scoutsunite_staff_sections()[ $f['section'] ] ?? '—' );
		echo $f['ancien'] ? ' <em>(ancien)</em>' : '';
	} elseif ( 'su_role' === $column ) {
		echo esc_html( scoutsunite_staff_role( $f ) . ( '' !== $f['fonction'] ? ' · ' . $f['fonction'] : '' ) );
	} elseif ( 'su_gsm' === $column ) {
		echo esc_html( $f['gsm'] );
	} elseif ( 'su_photo' === $column ) {
		echo '' !== scoutsunite_staff_photo_chemin( $post_id ) ? 'oui' : '<em>à ajouter</em>';
	}
}
add_action( 'manage_animateur_posts_custom_column', 'scoutsunite_staff_colonne', 10, 2 );

/**
 * La liste suit l'ordre du site : section, chef en tête, puis l'ordre choisi.
 *
 * @param WP_Query $query Requête.
 */
function scoutsunite_staff_liste_ordre( $query ) {
	if ( is_admin() && $query->is_main_query() && 'animateur' === $query->get( 'post_type' ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
}
add_action( 'pre_get_posts', 'scoutsunite_staff_liste_ordre' );

/**
 * Sous chaque fiche : « Imprimer la carte » (sans « Aperçu » ni « Modification rapide »).
 *
 * @param array   $actions Liens sous le titre.
 * @param WP_Post $post    Fiche.
 * @return array
 */
function scoutsunite_staff_actions( $actions, $post ) {
	if ( 'animateur' !== $post->post_type ) {
		return $actions;
	}
	unset( $actions['inline hide-if-no-js'], $actions['view'] );
	if ( current_user_can( 'manage_unite' ) && 'publish' === $post->post_status ) {
		$actions['imprimer'] = '<a href="' . esc_url( scoutsunite_staff_cartes_url( array( 'fiche' => $post->ID ) ) ) . '" target="_blank">Imprimer la carte</a>';
	}
	return $actions;
}
add_filter( 'post_row_actions', 'scoutsunite_staff_actions', 10, 2 );

/* ---------------------------------------------------------------------------
   Reprise des lignes du staff de Réglages → Unité
   ------------------------------------------------------------------------ */

/**
 * À la mise à jour, chaque ligne « Totem – Prénom (fonction) GSM » des staffs de section
 * devient une fiche, à compléter (nom, quali, photo). Une seule fois, et seulement
 * s'il n'y a encore aucune fiche.
 */
function scoutsunite_reprise_fiches() {
	if ( get_option( 'scoutsunite_reprise_fiches' ) ) {
		return;
	}
	update_option( 'scoutsunite_reprise_fiches', 1 );
	if ( get_posts( array( 'post_type' => 'animateur', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) ) ) {
		return;
	}
	foreach ( array_keys( scoutsunite_agenda_sections_age() ) as $section ) {
		$lignes = array();
		if ( '' !== scoutsunite_unite( $section . '_chef' ) ) {
			$lignes[] = array( scoutsunite_unite( $section . '_chef' ), true );
		}
		foreach ( array_filter( array_map( 'trim', explode( "\n", scoutsunite_unite( $section . '_staff' ) ) ) ) as $ligne ) {
			$lignes[] = array( $ligne, false );
		}
		foreach ( $lignes as $ordre => $ligne ) {
			list( $totem, $prenom, $fonction, $gsm ) = scoutsunite_unite_personne( $ligne[0] );
			$post_id = wp_insert_post(
				array(
					'post_type'   => 'animateur',
					'post_status' => 'publish',
					'post_title'  => scoutsunite_staff_appel( array( 'totem' => $totem, 'prenom' => $prenom ) ),
					'menu_order'  => $ordre,
					'meta_input'  => array(
						'su_section'  => $section,
						'su_chef'     => $ligne[1] ? '1' : '',
						'su_totem'    => $totem,
						'su_prenom'   => $prenom,
						'su_fonction' => $fonction,
						'su_gsm'      => $gsm,
						'su_genre'    => 'm',
					),
				)
			);
			if ( $post_id ) {
				scoutsunite_staff_titre( $post_id );
			}
		}
	}
}
add_action( 'init', 'scoutsunite_reprise_fiches', 20 );

/* ---------------------------------------------------------------------------
   Les cartes à imprimer
   ------------------------------------------------------------------------ */

/**
 * Adresse des cartes : toutes, celles d'une section ou d'une fiche.
 *
 * @param array $args 'section' ou 'fiche'.
 * @return string
 */
function scoutsunite_staff_cartes_url( $args = array() ) {
	return add_query_arg( array( 'action' => 'scoutsunite_cartes' ) + $args, admin_url( 'admin-post.php' ) );
}

/**
 * Recto d'une carte.
 *
 * @param array $f Fiche.
 * @return string HTML.
 */
function scoutsunite_staff_carte_recto( $f ) {
	$infos  = scoutsunite_staff_section_infos( $f['section'] );
	$logo   = 'iama' === $f['section'] ? '' : scoutsunite_extension_theme_url( 'assets/images/' . $infos['logo'] );
	$unite  = scoutsunite_staff_logo_unite();
	$photo  = scoutsunite_staff_photo_url( $f['id'] );
	$meute  = 'louveteaux' === $f['section'] && '' !== $f['totem_meute'];
	$grand  = $meute ? $f['totem_meute'] : ( '' !== $f['totem'] ? $f['totem'] : $f['prenom'] );
	$quali  = $meute ? trim( $f['totem'] . ' ' . $f['quali'] ) : ( '' !== $f['totem'] ? $f['quali'] : '' );
	$noms   = trim( $f['prenom'] . ' ' . $f['nom'] );

	$html  = '<div class="carte carte--recto carte--' . esc_attr( $f['section'] ) . '">';
	$html .= $logo ? '<img class="carte__logo" src="' . esc_url( $logo ) . '" alt="">' : '';
	$html .= $unite ? '<img class="carte__logo-unite" src="' . esc_url( $unite ) . '" alt="">' : '';
	$html .= '<div class="carte__corps">';
	if ( $photo ) {
		$html .= '<img class="carte__photo" src="' . esc_url( $photo ) . '" alt="">';
	} else {
		$initiale = mb_substr( $grand, 0, 1 );
		$html    .= '<span class="carte__photo carte__photo--vide">' . esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $initiale ) : strtoupper( $initiale ) ) . '</span>';
	}
	$html .= '<div class="carte__noms">';
	$html .= $meute ? '<span class="carte__jungle">Dans la Jungle</span>' : '';
	$html .= '<span class="carte__totem">' . esc_html( $grand ) . '</span>';
	$html .= '' !== $quali ? '<span class="carte__quali">' . esc_html( $quali ) . '</span>' : '';
	$html .= $noms !== $grand ? '<span class="carte__nom">' . esc_html( $noms ) . '</span>' : '';
	$html .= '</div></div>';
	$html .= '<div class="carte__bandeau">';
	if ( 'iama' === $f['section'] ) {
		$iama  = scoutsunite_extension_theme_url( 'assets/images/' . $infos['logo'] );
		$html .= $iama ? '<img class="carte__logo-bandeau" src="' . esc_url( $iama ) . '" alt="IAmA">' : '<strong>' . esc_html( $infos['nom'] ) . '</strong>';
	} else {
		$html .= '<strong>' . esc_html( $infos['nom'] ) . '</strong>';
	}
	$html .= '<span>' . esc_html( $infos['staff'] ) . '</span></div>';
	return $html . '</div>';
}

/**
 * Verso d'une carte.
 *
 * @param array $f Fiche.
 * @return string HTML.
 */
function scoutsunite_staff_carte_verso( $f ) {
	// Le staff d'unité accueille les nouvelles familles : son QR mène au formulaire d'inscription.
	if ( 'unite' === $f['section'] ) {
		$lien    = scoutsunite_unite( 'formulaire_inscription' );
		$legende = 'Le formulaire d’inscription';
	} else {
		$lien    = scoutsunite_whatsapp_lien( $f['section'] );
		$legende = 'Le groupe WhatsApp ' . scoutsunite_whatsapp_de( $f['section'] );
	}
	$qr   = '' !== $lien ? scoutsunite_qr_svg( $lien, '' ) : '';
	$site = untrailingslashit( preg_replace( '#^https?://(www\.)?#', '', home_url( '/' ) ) );

	$html  = '<div class="carte carte--verso carte--' . esc_attr( $f['section'] ) . '">';
	$html .= '<div class="carte__corps">';
	if ( '' !== $qr ) {
		$html .= '<div class="carte__qr">' . $qr . '<span>' . esc_html( $legende ) . '</span></div>';
	}
	$html .= '<div class="carte__infos">';
	if ( '' !== $f['gsm'] ) {
		$html .= '<span class="carte__libelle">GSM</span><span class="carte__gsm">' . esc_html( $f['gsm'] ) . '</span>';
	}
	$html .= '<span class="carte__libelle">Rôle</span><span class="carte__role">' . esc_html( scoutsunite_staff_role( $f ) ) . '</span>';
	$html .= '' !== $f['fonction'] ? '<span class="carte__fonction">' . esc_html( ucfirst( $f['fonction'] ) ) . '</span>' : '';
	$html .= '<span class="carte__unite"><strong>' . esc_html( get_bloginfo( 'name' ) ) . '</strong><span>' . esc_html( $site ) . '</span></span>';
	$html .= '</div></div></div>';
	return $html;
}

/**
 * Adresse du logo de l'unité (Réglages → Unité), '' s'il n'est pas choisi.
 *
 * @return string
 */
function scoutsunite_staff_logo_unite() {
	$logo = absint( scoutsunite_unite( 'logo' ) );
	return $logo ? (string) wp_get_attachment_image_url( $logo, 'medium' ) : '';
}

/**
 * Les planches : 10 cartes par feuille A4, chaque feuille de rectos suivie de ses versos.
 * Au verso, les colonnes sont inversées : en recto-verso « bord long », chaque verso
 * tombe derrière son recto.
 *
 * @param array[] $fiches Fiches.
 * @return string HTML.
 */
function scoutsunite_staff_planches( $fiches ) {
	$html = '';
	foreach ( array_chunk( $fiches, 10 ) as $lot ) {
		$rectos = '';
		$versos = array_fill( 0, count( $lot ) + count( $lot ) % 2, '<div class="carte carte--vide"></div>' );
		foreach ( $lot as $i => $f ) {
			$rectos .= scoutsunite_staff_carte_recto( $f );
			$versos[ $i - $i % 2 + ( 1 - $i % 2 ) ] = scoutsunite_staff_carte_verso( $f );
		}
		$html .= '<section class="planche" aria-label="Rectos">' . $rectos . '</section>';
		$html .= '<section class="planche planche--verso" aria-label="Versos">' . implode( '', $versos ) . '</section>';
	}
	return $html;
}

/**
 * Page des cartes, à imprimer en recto-verso.
 */
function scoutsunite_staff_cartes() {
	if ( ! current_user_can( 'manage_unite' ) ) {
		wp_die( 'Seuls les administrateurs et le staff d’unité impriment les cartes.', '', array( 'response' => 403 ) );
	}
	// Lecture seule : quelles cartes montrer.
	$section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$fiche   = isset( $_GET['fiche'] ) ? absint( $_GET['fiche'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
	if ( $fiche && 'animateur' === get_post_type( $fiche ) ) {
		$fiches = array( scoutsunite_staff_fiche( $fiche ) );
		$titre  = 'Carte de ' . scoutsunite_staff_appel( $fiches[0] );
	} else {
		$section = isset( scoutsunite_staff_sections()[ $section ] ) ? $section : '';
		$fiches  = scoutsunite_staff_fiches( $section );
		$titre   = 'Cartes ' . ( $section ? '– ' . scoutsunite_staff_sections()[ $section ] : 'de tout le staff' );
	}
	$css = scoutsunite_extension_theme_url( 'assets/css/imprimer.css' );

	nocache_headers();
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?php echo esc_html( $titre . ' · ' . get_bloginfo( 'name' ) ); ?></title>
<?php if ( $css ) : ?>
<link rel="stylesheet" href="<?php echo esc_url( add_query_arg( 'ver', wp_get_theme()->get( 'Version' ) . '.' . filemtime( get_theme_file_path( 'assets/css/imprimer.css' ) ), $css ) ); ?>">
<?php endif; ?>
</head>
<body class="cartes">
<nav class="barre" aria-label="Impression">
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=animateur' ) ); ?>">← Retour au staff</a>
	<button type="button" class="barre__imprimer" onclick="window.print()">Imprimer</button>
	<p class="barre__aide">Imprime en <strong>recto-verso, retourné sur le bord long</strong>, à 100 % (sans « Ajuster à la page »). Chaque feuille de rectos est suivie de ses versos. Découpe ensuite le long des traits.</p>
</nav>
<main>
	<?php if ( $fiches ) : ?>
		<?php echo scoutsunite_staff_planches( $fiches ); // phpcs:ignore WordPress.Security.EscapeOutput -- échappé à la construction. ?>
	<?php else : ?>
		<p class="cartes__vide">Aucune fiche à imprimer : ajoute le staff dans Staff → Ajouter.</p>
	<?php endif; ?>
</main>
</body>
</html>
	<?php
	exit;
}
add_action( 'admin_post_scoutsunite_cartes', 'scoutsunite_staff_cartes' );
