<?php
/**
 * Présentation publique des photos : une page et des entrées sur l'accueil et les sections.
 * Aucun album, aucune référence privée et aucune clé ne sont lus pour ces rendus publics.
 *
 * @package les-scouts-unite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Crée une seule fois la page de présentation, sans remplacer une page existante. */
function scoutsunite_photos_presentation_installer() {
	if ( false !== get_option( 'scoutsunite_photos_presentation_page', false ) ) {
		return;
	}
	$page = get_page_by_path( 'photos-de-camp', OBJECT, 'page' );
	$id = $page ? $page->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Photos de camp', 'post_name' => 'photos-de-camp', 'post_content' => '<!-- wp:scoutsunite/photos-presentation /-->' ), true );
	if ( ! is_wp_error( $id ) && $id ) {
		update_option( 'scoutsunite_photos_presentation_page', (int) $id, false );
	}
}
add_action( 'init', 'scoutsunite_photos_presentation_installer', 15 );

/**
 * Adresse de la présentation ; conserve le choix de supprimer ou dépublier la page.
 *
 * @return string
 */
function scoutsunite_photos_presentation_url() {
	$id = (int) get_option( 'scoutsunite_photos_presentation_page' );
	return $id && 'publish' === get_post_status( $id ) ? get_permalink( $id ) : scoutsunite_photos_url();
}

/**
 * Invitation publique, y compris pour les contenus créés avec une ancienne composition.
 *
 * @param string $section Section facultative, jamais une autorisation.
 * @return string HTML échappé.
 */
function scoutsunite_photos_promotion( $section = '' ) {
	$sections = scoutsunite_photos_sections();
	$section = is_string( $section ) && isset( $sections[ $section ] ) ? $section : '';
	$titre = $section ? 'Les souvenirs des ' . $sections[ $section ] : 'Le camp continue en images';
	$lien = scoutsunite_photos_presentation_url() . ( $section ? '#section-' . $section : '' );
	return '<aside class="photos-promo" data-photos-promo="1"><div><p class="photos-promo__surtitre">Les photos de camp</p><h2>' . esc_html( $titre ) . '</h2><p>Les grands jeux, les veillées, les moments partagés… Retrouve les photos dans l’espace réservé aux familles et choisis tes souvenirs préférés.</p></div><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="' . esc_url( $lien ) . '">Découvrir les photos de camp</a></div></div></aside>';
}

/**
 * Contenu de la page de présentation, conservé comme bloc modifiable dans WordPress.
 *
 * @return string
 */
function scoutsunite_photos_presentation() {
	$illustration = scoutsunite_extension_theme_url( 'assets/images/photos/coucher-soleil.png' );
	ob_start();
	?>
	<div class="photos-presentation">
	<section class="photos-presentation__intro"><div><p class="photos-promo__surtitre">Les souvenirs, à partager entre familles</p><h2>Le camp continue en images.</h2><p>Les constructions, les grands jeux, les veillées et les petits moments du quotidien : retrouve les souvenirs de la section de ton enfant dans un espace privé, facile à consulter sur ton téléphone.</p><div class="wp-block-buttons"><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( scoutsunite_photos_url() ); ?>">Ouvrir mes albums</a></div></div><p class="photos-presentation__aide">Première visite ? Ouvre le lien ou scanne le QR reçu du staff. Tu n’as pas de compte à créer.</p></div>
	<?php if ( $illustration ) : ?><figure><img src="<?php echo esc_url( $illustration ); ?>" width="960" height="640" alt="Illustration : une construction scoute au coucher du soleil"><figcaption>Image d’illustration. Les photos des camps restent dans les albums privés.</figcaption></figure><?php endif; ?></section>
	<section class="photos-presentation__etapes" aria-label="Comment retrouver les photos"><article><span aria-hidden="true">1</span><h3>Ouvre ton lien privé</h3><p>Le staff partage le lien et le QR de l’album. Chaque camp ou week-end a son propre accès, réservé aux familles concernées.</p></article><article><span aria-hidden="true">2</span><h3>Retrouve les moments du camp</h3><p>Parcours les photos en grand et les tags comme « Grand jeu » ou « Dîner ». Marque tes préférées avec le cœur : tes choix restent sur ton navigateur.</p></article><article><span aria-hidden="true">3</span><h3>Prépare ton souvenir</h3><p>Exporte ta sélection ou transmets une demande au staff avec un message. Le staff te recontactera pour les détails ; aucun paiement n’est demandé ici.</p></article></section>
	<section class="photos-presentation__sections"><h2>Les souvenirs de chaque section</h2><p>Tu ne trouves pas ton album ? Demande son lien au staff de la section. Un accès n’ouvre pas les autres albums ni les autres années.</p><div class="photos-presentation__sections-grille">
	<?php foreach ( scoutsunite_photos_sections() as $slug => $nom ) : ?><article id="section-<?php echo esc_attr( $slug ); ?>"><h3><?php echo esc_html( $nom ); ?></h3><p>Camps, week-ends et moments de la section, réunis dans les albums partagés avec les familles.</p><a class="lien-fleche" href="<?php echo esc_url( add_query_arg( 'section', $slug, scoutsunite_photos_url() ) ); ?>">Retrouver mes albums <?php echo esc_html( $nom ); ?></a></article><?php endforeach; ?>
	</div></section>
	<section class="photos-presentation__confiance"><h2>Des souvenirs qui restent dans le cercle des familles.</h2><p>Cette page de présentation n’affiche aucune photo privée. Le staff peut fermer un accès ou renouveler son lien. Garde le lien de ton album pour les familles du camp et respecte les personnes photographiées.</p><p>Un souci pour retrouver ton lien ? <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">Contacte le staff de ta section</a>.</p></section>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Entrée à la fin du contenu d'accueil, sans modifier les compositions déjà sauvegardées.
 *
 * @param string $contenu Bloc rendu.
 * @param array  $bloc Bloc source.
 * @return string
 */
function scoutsunite_photos_promotion_accueil( $contenu, $bloc ) {
	if ( ! is_admin() && is_front_page() && 'main' === ( $bloc['attrs']['tagName'] ?? '' ) && false === strpos( $contenu, 'data-photos-promo=' ) ) {
		$fin = strrpos( $contenu, '</main>' );
		if ( false !== $fin ) {
			return substr( $contenu, 0, $fin ) . scoutsunite_photos_promotion() . substr( $contenu, $fin );
		}
	}
	return $contenu;
}
add_filter( 'render_block_core/group', 'scoutsunite_photos_promotion_accueil', 20, 2 );

/**
 * Entrée dans les pages de section et les accueils personnalisés, sans doublon avec un bloc ajouté.
 *
 * @param string   $contenu Bloc rendu.
 * @param array    $source Bloc source.
 * @param WP_Block $bloc Instance avec l'identifiant de page.
 * @return string
 */
function scoutsunite_photos_promotion_contenu( $contenu, $source, $bloc ) {
	if ( is_admin() || ! is_singular( 'page' ) || (int) ( $bloc->context['postId'] ?? 0 ) !== get_queried_object_id() || false !== strpos( $contenu, 'data-photos-promo=' ) ) {
		return $contenu;
	}
	$section = get_post_field( 'post_name', get_queried_object_id() );
	return is_front_page() || isset( scoutsunite_photos_sections()[ $section ] ) ? $contenu . scoutsunite_photos_promotion( $section ) : $contenu;
}
add_filter( 'render_block_core/post-content', 'scoutsunite_photos_promotion_contenu', 20, 3 );
